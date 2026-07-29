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
	 * Settings\Admin and Settings\AdminSection are registered declaratively
	 * in appinfo/info.xml (<settings> block) and autowired by Nextcloud's
	 * DI container, so nothing to register here for those. This stays
	 * available for services/listeners as Milestone 3 needs them.
	 */
	public function register(IRegistrationContext $context): void {
	}

	/**
	 * Runtime bootstrapping, run on every request after registration.
	 */
	public function boot(IBootContext $context): void {
		// Nothing needed yet.
	}
}
