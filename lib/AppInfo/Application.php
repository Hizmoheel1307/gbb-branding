<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\AppInfo;

use OCA\GovMailBranding\Listener\FirstLoginListener;
use OCA\GovMailBranding\Listener\GlobalStyleListener;
use OCA\GovMailBranding\Listener\InlineImagesListener;
use OCA\GovMailBranding\SetupChecks\EmailTemplateCheck;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Mail\Events\BeforeMessageSent;
use OCP\User\Events\UserFirstTimeLoggedInEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'govmailbranding';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	/**
	 * Settings\Admin and Settings\AdminSection are registered declaratively
	 * in appinfo/info.xml (<settings> block) and autowired by Nextcloud's
	 * DI container, so nothing to register here for those.
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, GlobalStyleListener::class);
		$context->registerEventListener(UserFirstTimeLoggedInEvent::class, FirstLoginListener::class);
		$context->registerEventListener(BeforeMessageSent::class, InlineImagesListener::class);
		$context->registerSetupCheck(EmailTemplateCheck::class);
	}

	/*
	 * mail_template_class is NOT written here any more. Writing config.php
	 * from register() ran on every request on every node and skipped any
	 * check that the template actually renders. EmailTemplateGuard owns that
	 * key now; it is applied by the repair step (app:enable /
	 * maintenance:repair), the admin UI toggle, or
	 * `occ govmailbranding:email:override`.
	 */

	/**
	 * Runtime bootstrapping, run on every request after registration.
	 */
	public function boot(IBootContext $context): void {
		// Nothing needed yet.
	}
}
