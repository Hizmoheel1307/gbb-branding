<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Controller;

use OCA\GovMailBranding\Service\NotificationBrandingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class NotificationBrandingController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private NotificationBrandingService $service,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
	) {
		parent::__construct($appName, $request);
	}

	private function isAdmin(): bool {
		$user = $this->userSession->getUser();
		return $user !== null && $this->groupManager->isAdmin($user->getUID());
	}

	public function index(): JSONResponse {
		if (!$this->isAdmin()) {
			return new JSONResponse(['error' => 'Admin access required.'], Http::STATUS_FORBIDDEN);
		}
		return new JSONResponse(['settings' => $this->service->get(), 'page' => $this->service->forPage()]);
	}

	public function save(array $settings = []): JSONResponse {
		if (!$this->isAdmin()) {
			return new JSONResponse(['error' => 'Admin access required.'], Http::STATUS_FORBIDDEN);
		}
		$saved = $this->service->save($settings);
		return new JSONResponse(['settings' => $saved, 'page' => $this->service->forPage()]);
	}
}
