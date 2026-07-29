<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCA\GovMailBranding\Service\BrandingConfigService;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	public function __construct(
		private IInitialState $initialState,
		private BrandingConfigService $configService,
	) {
	}

	public function getForm(): TemplateResponse {
		// Pass current saved settings to the frontend so the Vue app
		// doesn't need a separate round-trip just to populate the form.
		$this->initialState->provideInitialState(
			'settings',
			$this->configService->getAll()
		);

		return new TemplateResponse('govmailbranding', 'admin', [], '');
	}

	public function getSection(): string {
		return 'govmailbranding';
	}

	public function getPriority(): int {
		return 10;
	}
}
