<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\Headers\AutoSubmitted;
use OCP\Mail\IMailer;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sends Nextcloud's own welcome email (settings.Welcome) - which our
 * template restyles - to users who sign in with their mail password and so
 * were never "created" by an admin.
 *
 * Built with the Settings app's NewUserMailHelper, i.e. the exact same
 * email Nextcloud sends when an admin creates a user, so both paths look
 * identical.
 */
class WelcomeMailService {
	private const APP_ID = 'govmailbranding';
	public const SENT_KEY = 'welcome_sent';
	private const SENDING_KEY = 'welcome_sending';
	private const HELPER = 'OCA\\Settings\\Mailer\\NewUserMailHelper';

	public function __construct(
		private IUserManager $userManager,
		private IMailer $mailer,
		private IConfig $config,
		private EmailCustomizationService $customization,
		private LoggerInterface $logger,
	) {
	}

	public function resolveAddress(IUser $user): ?string {
		$email = (string)($user->getEMailAddress() ?? '');
		if ($email !== '' && $this->mailer->validateMailAddress($email)) {
			return $email;
		}
		$welcome = $this->customization->getWelcome();
		$uid = $user->getUID();
		if ($welcome['login_as_email'] && $this->mailer->validateMailAddress($uid)) {
			return $uid;
		}
		return null;
	}

	public function alreadySent(string $uid): bool {
		return $this->config->getUserValue($uid, self::APP_ID, self::SENT_KEY, '') !== '';
	}

	/**
	 * @param string|null $to send here instead of the user's own address (tests)
	 * @param bool $force send even if this user already got one
	 * @param bool $markSent remember that this user got their welcome email
	 * @return array{ok: bool, to: string, error: string, skipped: bool}
	 */
	public function send(string $uid, ?string $to = null, bool $force = false, bool $markSent = true): array {
		$result = ['ok' => false, 'to' => '', 'error' => '', 'skipped' => false];

		$user = $this->userManager->get($uid);
		if ($user === null) {
			$result['error'] = "No user {$uid}.";
			return $result;
		}
		if (!$force && $this->alreadySent($uid)) {
			$result['skipped'] = true;
			$result['error'] = 'Welcome email already sent to this user.';
			return $result;
		}

		// The first-login send runs in the background with cron as a fallback;
		// a short claim stops both from sending at the same moment.
		if (!$force) {
			$claimed = (int)$this->config->getUserValue($uid, self::APP_ID, self::SENDING_KEY, '0');
			if ($claimed > time() - 300) {
				$result['skipped'] = true;
				$result['error'] = 'Welcome email is already being sent.';
				return $result;
			}
			$this->config->setUserValue($uid, self::APP_ID, self::SENDING_KEY, (string)time());
		}

		try {
			return $this->deliver($user, $uid, $to, $markSent, $result);
		} finally {
			if (!$force) {
				$this->config->deleteUserValue($uid, self::APP_ID, self::SENDING_KEY);
			}
		}
	}

	private function deliver(IUser $user, string $uid, ?string $to, bool $markSent, array $result): array {
		$address = $to !== null && $to !== '' ? $to : $this->resolveAddress($user);
		if ($address === null || !$this->mailer->validateMailAddress($address)) {
			$result['skipped'] = true;
			$result['error'] = 'User has no usable email address.';
			return $result;
		}
		$result['to'] = $address;

		if (!class_exists(self::HELPER)) {
			$result['error'] = 'The Settings app is not available, cannot build the welcome email.';
			return $result;
		}

		try {
			$helper = Server::get(self::HELPER);
			$template = $helper->generateTemplate($user, false);

			$message = $this->mailer->createMessage();
			$message->setTo([$address => $user->getDisplayName()]);
			$message->useTemplate($template);
			$message->setAutoSubmitted(AutoSubmitted::VALUE_AUTO_GENERATED);
			$failed = $this->mailer->send($message);

			if ($failed !== []) {
				$result['error'] = 'Sending failed for ' . implode(', ', $failed) . ' - check the Nextcloud log for the SMTP error.';
				return $result;
			}
			if ($markSent) {
				$this->config->setUserValue($uid, self::APP_ID, self::SENT_KEY, (string)time());
			}
			$result['ok'] = true;
		} catch (Throwable $e) {
			$result['error'] = $e->getMessage();
			$this->logger->warning('GovMail Branding: welcome email to ' . $uid . ' failed: ' . $e->getMessage(), ['app' => self::APP_ID]);
		}
		return $result;
	}
}
