<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCA\GovMailBranding\Mail\BrandedEmailTemplate;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Owns the 'mail_template_class' system setting. Nothing else in the app
 * should write it.
 *
 * - enable(): render smoke test first, only then point config.php at our
 *   template. A failing test leaves stock emails untouched.
 * - disable(): removes the key, but only if it still points at us.
 * - Never overwrites a template class that belongs to someone else.
 *
 * Note: config.php is per app server. In a multi-node setup each node is
 * reconciled by the repair step that runs on deploy (app:enable /
 * maintenance:repair), or by `occ govmailbranding:email:override` on that
 * node. status() reports the hostname so the UI shows which node answered.
 */
class EmailTemplateGuard {
	public const APP_ID = 'govmailbranding';
	public const SYSTEM_KEY = 'mail_template_class';
	public const ENABLED_KEY = 'email_override_enabled';
	public const LAST_ERROR_KEY = 'email_override_last_error';
	public const LAST_TEST_KEY = 'email_last_test';

	public function __construct(
		private IConfig $config,
		private IMailer $mailer,
		private Defaults $defaults,
		private IURLGenerator $urlGenerator,
		private IFactory $l10nFactory,
		private LoggerInterface $logger,
	) {
	}

	public function isEnabled(): bool {
		// Default on, to keep the behaviour the app had before this flag.
		return $this->config->getAppValue(self::APP_ID, self::ENABLED_KEY, '1') === '1';
	}

	/**
	 * States:
	 * - active:     enabled, config.php points at us, Mailer really uses it
	 * - fallback:   config.php points at us but Mailer built the stock
	 *               template (class failed to load) - emails are unbranded
	 * - pending:    enabled but config.php doesn't point at us on this node
	 * - foreign:    config.php points at another app's template class
	 * - disabled:   switched off and config.php is clean
	 * - stale:      switched off but config.php still points at us
	 */
	public function status(): array {
		$systemValue = (string)$this->config->getSystemValue(self::SYSTEM_KEY, '');
		$pointsAtUs = $systemValue === BrandedEmailTemplate::class;
		$enabled = $this->isEnabled();

		$inUse = '';
		try {
			$inUse = get_class($this->mailer->createEMailTemplate('govmailbranding.status'));
		} catch (Throwable $e) {
			$inUse = 'error: ' . $e->getMessage();
		}

		if ($systemValue !== '' && !$pointsAtUs) {
			$state = 'foreign';
		} elseif ($enabled && $pointsAtUs) {
			$state = $inUse === BrandedEmailTemplate::class ? 'active' : 'fallback';
		} elseif ($enabled) {
			$state = 'pending';
		} else {
			$state = $pointsAtUs ? 'stale' : 'disabled';
		}

		return [
			'state' => $state,
			'enabled' => $enabled,
			'systemValue' => $systemValue,
			'templateInUse' => $inUse,
			'host' => (string)gethostname(),
			'lastError' => $this->config->getAppValue(self::APP_ID, self::LAST_ERROR_KEY, ''),
			'lastTest' => $this->getLastTest(),
		];
	}

	/**
	 * @return array{ok: bool, error: string}
	 */
	public function smokeTest(): array {
		try {
			$template = new BrandedEmailTemplate(
				$this->defaults,
				$this->urlGenerator,
				$this->l10nFactory,
				null,
				null,
				'govmailbranding.smoketest',
				[],
			);
			$template->setSubject('Smoke test');
			$template->addHeader();
			$template->addHeading('Smoke test');
			$template->addBodyText('Rendering check.');
			$template->addFooter();
			$html = $template->renderHtml();
			$text = $template->renderText();

			if (!str_contains($html, '</html>')) {
				return ['ok' => false, 'error' => 'Rendered HTML is incomplete (no closing </html>).'];
			}
			if (trim($text) === '') {
				return ['ok' => false, 'error' => 'Rendered plain-text body is empty.'];
			}
			return ['ok' => true, 'error' => ''];
		} catch (Throwable $e) {
			return ['ok' => false, 'error' => get_class($e) . ': ' . $e->getMessage()];
		}
	}

	public function enable(): array {
		$this->config->setAppValue(self::APP_ID, self::ENABLED_KEY, '1');
		$this->apply();
		return $this->status();
	}

	public function disable(): array {
		$this->config->setAppValue(self::APP_ID, self::ENABLED_KEY, '0');
		$this->removeIfOurs();
		$this->config->setAppValue(self::APP_ID, self::LAST_ERROR_KEY, '');
		return $this->status();
	}

	/**
	 * Bring this node's config.php in line with the enabled flag.
	 */
	public function reconcile(): array {
		if ($this->isEnabled()) {
			$this->apply();
		} else {
			$this->removeIfOurs();
		}
		return $this->status();
	}

	public function removeIfOurs(): void {
		if ($this->config->getSystemValue(self::SYSTEM_KEY, '') === BrandedEmailTemplate::class) {
			$this->config->deleteSystemValue(self::SYSTEM_KEY);
		}
	}

	private function apply(): void {
		$current = (string)$this->config->getSystemValue(self::SYSTEM_KEY, '');
		if ($current !== '' && $current !== BrandedEmailTemplate::class) {
			$this->setError("config.php already sets mail_template_class to {$current}; not overwriting it.");
			return;
		}

		$result = $this->smokeTest();
		if (!$result['ok']) {
			$this->removeIfOurs();
			$this->setError('Smoke test failed, stock email template kept: ' . $result['error']);
			$this->logger->error('GovMail Branding email template disabled: ' . $result['error'], ['app' => self::APP_ID]);
			return;
		}

		if ($current !== BrandedEmailTemplate::class) {
			$this->config->setSystemValue(self::SYSTEM_KEY, BrandedEmailTemplate::class);
		}
		$this->setError('');
	}

	private function setError(string $message): void {
		$this->config->setAppValue(self::APP_ID, self::LAST_ERROR_KEY, $message);
	}

	public function getLastTest(): ?array {
		$raw = $this->config->getAppValue(self::APP_ID, self::LAST_TEST_KEY, '');
		if ($raw === '') {
			return null;
		}
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : null;
	}

	public function recordTest(array $result): void {
		$this->config->setAppValue(self::APP_ID, self::LAST_TEST_KEY, (string)json_encode($result));
	}
}
