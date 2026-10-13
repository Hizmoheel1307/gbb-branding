<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\SetupChecks;

use OCA\GovMailBranding\Service\EmailTemplateGuard;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

/**
 * Surfaces email-branding problems on Administration settings > Overview.
 */
class EmailTemplateCheck implements ISetupCheck {
	public function __construct(
		private EmailTemplateGuard $guard,
	) {
	}

	public function getCategory(): string {
		return 'email';
	}

	public function getName(): string {
		return 'GovMail email branding';
	}

	public function run(): SetupResult {
		$status = $this->guard->status();
		$host = $status['host'];

		$lastTest = $status['lastTest'];
		if ($lastTest !== null && !($lastTest['ok'] ?? false)) {
			return SetupResult::warning('The last GovMail branding test email failed: ' . ($lastTest['error'] ?? 'unknown error'));
		}

		return match ($status['state']) {
			'active' => $lastTest === null
				? SetupResult::info("Branded email template is active on {$host}, but no test email has been sent yet. Send one from GovMail Branding > Email Branding.")
				: SetupResult::success("Branded email template is active on {$host}."),
			'fallback' => SetupResult::warning("config.php points at the GovMail branded template on {$host}, but Nextcloud is using the stock template, so emails go out unbranded. Template in use: {$status['templateInUse']}"),
			'pending' => SetupResult::warning("GovMail email branding is switched on but not applied on {$host}. " . ($status['lastError'] !== '' ? $status['lastError'] : 'Run: occ govmailbranding:email:override on')),
			'foreign' => SetupResult::info("Another template class ({$status['systemValue']}) owns mail_template_class on {$host}; GovMail email branding is not applied."),
			'stale' => SetupResult::warning("GovMail email branding is switched off but config.php on {$host} still points at it. Run: occ govmailbranding:email:override off"),
			default => SetupResult::success('GovMail email branding is switched off; stock Nextcloud emails are used.'),
		};
	}
}
