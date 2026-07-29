<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Controller;

use OCA\Theming\ImageManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Uploads images straight into Nextcloud's own Theming app storage via its
 * ImageManager service, so these genuinely replace the instance's live
 * logo/favicon/background - not a separate copy in our own app.
 *
 * Note: OCA\Theming\ImageManager is an internal class of a core app, not
 * part of the stable OCP API. It's stable in practice (Theming is
 * always-enabled and this class has been consistent across recent major
 * versions), but a future Nextcloud upgrade could change its signature.
 * If uploads here start failing after a server upgrade, this is the
 * first place to check.
 */
class ThemingImageController extends Controller {
	// Maps our field names to Nextcloud Theming's real image keys.
	private const KEY_MAP = [
		'logo' => 'logo',
		'header_logo' => 'logoheader',
		'favicon' => 'favicon',
		'background' => 'background',
	];

	public function __construct(
		string $appName,
		IRequest $request,
		private ImageManager $themingImageManager,
		private IConfig $config,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
	) {
		parent::__construct($appName, $request);
	}

	public function upload(string $key): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null || !$this->groupManager->isAdmin($user->getUID())) {
			return new JSONResponse(['error' => 'Admin access required.'], Http::STATUS_FORBIDDEN);
		}

		if (!isset(self::KEY_MAP[$key])) {
			return new JSONResponse(['error' => 'Unknown image field.'], Http::STATUS_BAD_REQUEST);
		}
		$themingKey = self::KEY_MAP[$key];

		$file = $this->request->getUploadedFile('file');
		if ($file === null || !isset($file['tmp_name'], $file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
			return new JSONResponse(['error' => 'No valid file uploaded.'], Http::STATUS_BAD_REQUEST);
		}

		try {
			// updateImage validates the mime type itself against what Theming
			// actually supports per key and throws if unsupported.
			$mime = $this->themingImageManager->updateImage($themingKey, $file['tmp_name']);
		} catch (\Exception $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		// Bump the cache buster so browsers/clients pick up the new image
		// immediately instead of serving a stale cached copy.
		$current = (int)$this->config->getAppValue('theming', 'cachebuster', '0');
		$this->config->setAppValue('theming', 'cachebuster', (string)($current + 1));

		return new JSONResponse([
			'key' => $key,
			'mime' => $mime,
			'url' => $this->themingImageManager->getImageUrl($themingKey),
		]);
	}
}
