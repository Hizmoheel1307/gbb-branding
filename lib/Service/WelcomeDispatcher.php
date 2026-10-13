<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCA\GovMailBranding\BackgroundJob\SendWelcomeEmail;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Gets the welcome email out within seconds of a first sign-in without
 * making the login wait for SMTP:
 *
 * 1. Starts `occ govmailbranding:welcome:send <uid>` as a detached
 *    background process (setsid/nohup), so the login request returns
 *    immediately and the email goes out in parallel.
 * 2. Always also queues the cron job as a safety net. If the immediate send
 *    worked, the job finds the user already welcomed and does nothing; if
 *    it failed (SMTP down, exec disabled), cron delivers it later.
 *
 * Sending inside the login request itself is avoided on purpose: it would
 * add the SMTP round-trip to the login and hold the session lock.
 */
class WelcomeDispatcher {
	public function __construct(
		private IJobList $jobList,
		private LoggerInterface $logger,
	) {
	}

	public function dispatch(string $uid, bool $immediate): bool {
		$this->jobList->add(SendWelcomeEmail::class, ['uid' => $uid]);

		if (!$immediate) {
			return false;
		}
		$command = $this->command($uid);
		if ($command === null) {
			return false;
		}
		try {
			exec($command, $output, $code);
			return $code === 0;
		} catch (Throwable $e) {
			$this->logger->info('GovMail Branding: immediate welcome send unavailable, cron will send it: ' . $e->getMessage(), ['app' => 'govmailbranding']);
			return false;
		}
	}

	/** null when this server can't start background processes. */
	public function command(string $uid): ?string {
		if (!self::execAvailable()) {
			return null;
		}
		$php = self::phpCli();
		$occ = self::serverRoot() . '/occ';
		if ($php === null || !is_file($occ)) {
			return null;
		}
		$prefix = is_executable('/usr/bin/setsid') ? '/usr/bin/setsid ' : (is_executable('/usr/bin/nohup') ? '/usr/bin/nohup ' : '');
		return $prefix . escapeshellarg($php) . ' ' . escapeshellarg($occ)
			. ' govmailbranding:welcome:send ' . escapeshellarg($uid)
			. ' > /dev/null 2>&1 < /dev/null &';
	}

	public static function execAvailable(): bool {
		if (!function_exists('exec')) {
			return false;
		}
		$disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
		return !in_array('exec', $disabled, true);
	}

	/**
	 * The PHP CLI binary. Under PHP-FPM, PHP_BINARY is the FPM daemon, so look
	 * for the CLI next to it (same PHP version first).
	 */
	public static function phpCli(): ?string {
		$candidates = [];
		if (PHP_SAPI === 'cli' && PHP_BINARY !== '') {
			$candidates[] = PHP_BINARY;
		}
		$candidates[] = PHP_BINDIR . '/php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$candidates[] = PHP_BINDIR . '/php';
		$candidates[] = '/usr/bin/php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$candidates[] = '/usr/bin/php';
		foreach ($candidates as $c) {
			if (@is_file($c) && @is_executable($c)) {
				return $c;
			}
		}
		return null;
	}

	private static function serverRoot(): string {
		return class_exists('\OC') && isset(\OC::$SERVERROOT) ? \OC::$SERVERROOT : dirname(__DIR__, 4);
	}
}
