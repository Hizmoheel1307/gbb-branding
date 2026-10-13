<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Migration;

use OCA\GovMailBranding\Service\EmailTemplateGuard;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * On app removal, don't leave config.php pointing at a class that no
 * longer exists.
 */
class RemoveEmailTemplate implements IRepairStep {
	public function __construct(
		private EmailTemplateGuard $guard,
	) {
	}

	public function getName(): string {
		return 'GovMail Branding: remove branded email template';
	}

	public function run(IOutput $output): void {
		$this->guard->removeIfOurs();
	}
}
