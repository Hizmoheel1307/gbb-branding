<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Controller;

use OCA\GovMailBranding\Service\EmailTemplateGuard;
use OCA\GovMailBranding\Service\TestEmailService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class EmailController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private EmailTemplateGuard $guard,
		private TestEmailService $testEmail,
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

	public function status(): JSONResponse {
		if ($denied = $this->requireAdmin()) {
			return $denied;
		}
		return new JSONResponse($this->guard->status());
	}

	public function setOverride(bool $enabled): JSONResponse {
		if ($denied = $this->requireAdmin()) {
			return $denied;
		}
		return new JSONResponse($enabled ? $this->guard->enable() : $this->guard->disable());
	}

	public function sendTest(string $to = ''): JSONResponse {
		if ($denied = $this->requireAdmin()) {
			return $denied;
		}
		if ($to === '') {
			$to = (string)$this->userSession->getUser()?->getEMailAddress();
		}
		$result = $this->testEmail->send($to, 'web');
		return new JSONResponse(
			['result' => $result, 'status' => $this->guard->status()],
			$result['ok'] ? Http::STATUS_OK : Http::STATUS_UNPROCESSABLE_ENTITY,
		);
	}
}
