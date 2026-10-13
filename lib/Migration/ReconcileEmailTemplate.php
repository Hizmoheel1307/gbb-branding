<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Migration;

use OCA\GovMailBranding\Service\EmailTemplateGuard;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Runs on app install/enable, upgrade and `occ maintenance:repair`, so every
 * deploy re-applies (or removes) the template override on the node it runs
 * on - after a smoke test, never blindly.
 */
class ReconcileEmailTemplate implements IRepairStep {
	public function __construct(
		private EmailTemplateGuard $guard,
	) {
	}

	public function getName(): string {
		return 'GovMail Branding: apply branded email template';
	}

	public function run(IOutput $output): void {
		$status = $this->guard->reconcile();
		$output->info('GovMail email branding on ' . $status['host'] . ': ' . $status['state']);
		if ($status['lastError'] !== '') {
			$output->warning($status['lastError']);
		}
	}
}
