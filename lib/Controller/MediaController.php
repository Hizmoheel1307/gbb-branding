<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Controller;

use OCA\GovMailBranding\Service\BrandingConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class MediaController extends Controller {
	// Every image field across all tabs, mapped to its allowed mime types.
	private const IMAGE_SLOTS = [
		'general_logo' => ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'],
		'general_header_logo' => ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'],
		'general_favicon' => ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'],
		'general_background_image' => ['image/png', 'image/jpeg', 'image/webp'],
		'login_background' => ['image/png', 'image/jpeg', 'image/webp'],
		'login_logo' => ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'],
		'email_logo' => ['image/png', 'image/jpeg'],
		'email_header' => ['image/png', 'image/jpeg'],
	];

	private const MAX_BYTES = 5 * 1024 * 1024; // 5MB

	public function __construct(
		string $appName,
		IRequest $request,
		private IAppData $appData,
		private BrandingConfigService $configService,
		private IURLGenerator $urlGenerator,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	public function upload(string $slot): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null || !$this->groupManager->isAdmin($user->getUID())) {
			return new JSONResponse(['error' => 'Admin access required.'], Http::STATUS_FORBIDDEN);
		}

		if (!array_key_exists($slot, self::IMAGE_SLOTS)) {
			return new JSONResponse(['error' => 'Unknown image slot.'], Http::STATUS_BAD_REQUEST);
		}

		$file = $this->request->getUploadedFile('file');
		if ($file === null || !isset($file['tmp_name'], $file['size'], $file['error'])) {
			return new JSONResponse(['error' => 'No file uploaded.'], Http::STATUS_BAD_REQUEST);
		}

		if ($file['error'] !== UPLOAD_ERR_OK) {
			return new JSONResponse(['error' => 'Upload failed.'], Http::STATUS_BAD_REQUEST);
		}

		if ($file['size'] > self::MAX_BYTES) {
			return new JSONResponse(['error' => 'File exceeds 5MB limit.'], Http::STATUS_BAD_REQUEST);
		}

		$mimeType = mime_content_type($file['tmp_name']);
		if ($mimeType === false || !in_array($mimeType, self::IMAGE_SLOTS[$slot], true)) {
			return new JSONResponse(['error' => 'Unsupported file type: ' . $mimeType], Http::STATUS_BAD_REQUEST);
		}

		try {
			$folder = $this->getOrCreateFolder('branding-media');
			$fileName = $slot . '_' . time() . '_' . bin2hex(random_bytes(4));

			// Remove any previous file(s) for this slot before saving the new one
			foreach ($folder->getDirectoryListing() as $existing) {
				if (str_starts_with($existing->getName(), $slot . '_')) {
					$existing->delete();
				}
			}

			$newFile = $folder->newFile($fileName);
			$newFile->putContent(file_get_contents($file['tmp_name']));

			$this->configService->setMany([$slot => $fileName]);

			return new JSONResponse([
				'slot' => $slot,
				'fileName' => $fileName,
				'url' => $this->buildMediaUrl($fileName),
			]);
		} catch (NotPermittedException|NotFoundException $e) {
			$this->logger->error('GovMail Branding media upload failed', ['exception' => $e]);
			return new JSONResponse(['error' => 'Server storage error.'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	public function get(string $fileName): Http\Response {
		try {
			$folder = $this->getOrCreateFolder('branding-media');
			$file = $folder->getFile($fileName);
			return new Http\DataDisplayResponse($file->getContent(), Http::STATUS_OK, ['Content-Type' => $file->getMimeType()]);
		} catch (NotPermittedException|NotFoundException $e) {
			return new Http\NotFoundResponse();
		}
	}

	private function getOrCreateFolder(string $name): ISimpleFolder {
		try {
			return $this->appData->getFolder($name);
		} catch (NotFoundException) {
			return $this->appData->newFolder($name);
		}
	}

	private function buildMediaUrl(string $fileName): string {
		return $this->urlGenerator->linkToRoute(
			'govmailbranding.media.get',
			['fileName' => $fileName]
		);
	}
}
