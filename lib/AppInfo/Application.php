<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\AppInfo;

use OCA\GovMailBranding\Listener\LoginPageListener;
use OCA\GovMailBranding\Listener\GlobalStyleListener;
use OCA\GovMailBranding\Mail\BrandedEmailTemplate;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\IConfig;

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
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, LoginPageListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, GlobalStyleListener::class);
		$this->registerBrandedMailTemplate();
	}

	/**
	 * Points Nextcloud's mailer at our custom footer template. Guarded so
	 * it only writes to config once, not on every request. Known risk:
	 * https://github.com/nextcloud/server/issues/45392 - verify with a
	 * real test email, don't rely on absence of errors alone.
	 */
	private function registerBrandedMailTemplate(): void {
		/** @var IConfig $config */
		$config = $this->getContainer()->get(IConfig::class);
		$current = $config->getSystemValue('mail_template_class', '');
		if ($current !== BrandedEmailTemplate::class) {
			$config->setSystemValue('mail_template_class', BrandedEmailTemplate::class);
		}
	}

	/**
	 * Runtime bootstrapping, run on every request after registration.
	 */
	public function boot(IBootContext $context): void {
		// Nothing needed yet.
	}
}
