<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Controller;

use OCA\GovMailBranding\Service\ThemingBridgeService;
use OCA\Theming\ImageManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class ThemingSettingsController extends Controller {
	private const URL_KEYS = ['theming_web_link', 'theming_legal_notice', 'theming_privacy_policy'];
	private const COLOR_KEYS = ['theming_primary_color', 'theming_background_color'];

	private const IMAGE_KEY_MAP = [
		'logo' => 'logo',
		'header_logo' => 'logoheader',
		'favicon' => 'favicon',
		'background' => 'background',
	];

	public function __construct(
		string $appName,
		IRequest $request,
		private ThemingBridgeService $themingBridge,
		private ImageManager $themingImageManager,
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

		$images = [];
		foreach (self::IMAGE_KEY_MAP as $ourKey => $themingKey) {
			$images[$ourKey] = $this->themingImageManager->getImageUrl($themingKey);
		}

		return new JSONResponse([
			'values' => $this->themingBridge->getAll(),
			'images' => $images,
		]);
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
			return new JSONResponse(['errors' => $errors], Http::STATUS_BAD_REQUEST);
		}

		$saved = $this->themingBridge->setMany($values);

		return new JSONResponse(['saved' => $saved]);
	}

	/**
	 * @param array<string, string> $values
	 * @return array<string, string>
	 */
	private function validate(array $values): array {
		$errors = [];
		foreach ($values as $key => $value) {
			if ($value === '') {
				continue;
			}
			if (in_array($key, self::URL_KEYS, true) && !filter_var($value, FILTER_VALIDATE_URL)) {
				$errors[$key] = 'Must be a valid URL, e.g. https://example.com.';
			}

			if (in_array($key, self::COLOR_KEYS, true) && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
				$errors[$key] = 'Must be a hex color, e.g. #1B8018.';
			}
		}
		return $errors;
	}
}
