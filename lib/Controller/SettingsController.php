<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Controller;

use OCA\GovMailBranding\Service\BrandingConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller {
	private const EMAIL_KEYS = ['general_support_email'];
	private const COLOR_KEYS = ['color_primary', 'color_background', 'color_header', 'color_accent'];
	private const URL_KEYS = ['general_website', 'general_web_link'];

	public function __construct(
		string $appName,
		IRequest $request,
		private BrandingConfigService $configService,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
	) {
		parent::__construct($appName, $request);
	}

	private function requireAdmin(): ?JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null || !$this->groupManager->isAdmin($user->getUID())) {
			return new JSONResponse(['error' => 'Admin access required.'], Http::STATUS_FORBIDDEN);
		}
		return null;
	}

	public function index(): JSONResponse {
		if ($denied = $this->requireAdmin()) {
			return $denied;
		}
		return new JSONResponse($this->configService->getAll());
	}

	/**
	 * @param array<string, string> $values
	 */
	public function save(array $values): JSONResponse {
		if ($denied = $this->requireAdmin()) {
			return $denied;
		}

		$errors = $this->validate($values);

		if (!empty($errors)) {
			return new JSONResponse(
				['errors' => $errors],
				Http::STATUS_BAD_REQUEST
			);
		}

		$saved = $this->configService->setMany($values);

		return new JSONResponse([
			'saved' => $saved,
		]);
	}

	/**
	 * @param array<string, string> $values
	 * @return array<string, string> map of key => error message
	 */
	private function validate(array $values): array {
		$errors = [];

		foreach ($values as $key => $value) {
			if ($value === '') {
				continue; // empty is allowed, fields aren't required
			}

			if (in_array($key, self::EMAIL_KEYS, true) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
				$errors[$key] = 'Must be a valid email address.';
				continue;
			}

			if (in_array($key, self::COLOR_KEYS, true) && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
				$errors[$key] = 'Must be a hex color, e.g. #1A2B3C.';
				continue;
			}

			if (in_array($key, self::URL_KEYS, true) && !filter_var($value, FILTER_VALIDATE_URL)) {
				$errors[$key] = 'Must be a valid URL, e.g. https://example.com.';
				continue;
			}
		}

		return $errors;
	}
}
