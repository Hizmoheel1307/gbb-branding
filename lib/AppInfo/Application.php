<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'govmailbranding';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	/**
	 * Register services, listeners, and admin settings sections here
	 * as they're built out in Milestone 2 and 3.
	 */
	public function register(IRegistrationContext $context): void {
		// Example (uncomment when the settings class exists):
		// $context->registerService(BrandingService::class, function ($c) {
		//     return new BrandingService($c->get(IConfig::class));
		// });
	}

	/**
	 * Runtime bootstrapping, run on every request after registration.
	 */
	public function boot(IBootContext $context): void {
		// Nothing needed yet.
	}
}
