<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\BackgroundJob;

use OCA\GovMailBranding\Service\WelcomeMailService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

/**
 * Sends one welcome email from cron, so a user's first login never waits on
 * SMTP and a go-live wave of 1,000 logins doesn't hammer the mail server
 * from web requests.
 */
class SendWelcomeEmail extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private WelcomeMailService $welcome,
	) {
		parent::__construct($time);
	}

	protected function run($argument): void {
		$uid = (string)($argument['uid'] ?? '');
		if ($uid !== '') {
			$this->welcome->send($uid);
		}
	}
}
