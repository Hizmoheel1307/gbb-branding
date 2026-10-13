<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Controller;

use OC\Mail\EMailTemplate;
use OCA\GovMailBranding\Service\EmailCatalog;
use OCA\GovMailBranding\Service\EmailCustomizationService;
use OCA\GovMailBranding\Service\WelcomeMailService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Server;
use Throwable;

class EmailTemplatesController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private EmailCustomizationService $svc,
		private WelcomeMailService $welcome,
		private IMailer $mailer,
		private IURLGenerator $urlGenerator,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
	) {
		parent::__construct($appName, $request);
	}

	private function admin(): ?IUser {
		$user = $this->userSession->getUser();
		return ($user !== null && $this->groupManager->isAdmin($user->getUID())) ? $user : null;
	}

	private function denied(): JSONResponse {
		return new JSONResponse(['error' => 'Admin access required.'], Http::STATUS_FORBIDDEN);
	}

	public function index(): JSONResponse {
		if ($this->admin() === null) {
			return $this->denied();
		}
		$emails = [];
		foreach (EmailCatalog::all() as $id => $entry) {
			$emails[] = $this->describe($id, $entry);
		}
		$known = array_keys(EmailCatalog::all());
		foreach (array_keys($this->svc->getSeen()) as $id) {
			if (!in_array($id, $known, true) && !str_starts_with($id, 'dav.calendarInvite.')) {
				$emails[] = $this->describe($id, EmailCatalog::generic($id));
			}
		}
		// Overrides saved for emails we no longer list (e.g. app removed)
		foreach (array_keys($this->svc->getOverrides()) as $id) {
			if (!in_array($id, array_column($emails, 'id'), true)) {
				$emails[] = $this->describe($id, EmailCatalog::generic($id));
			}
		}

		return new JSONResponse([
			'emails' => $emails,
			'overrides' => (object)$this->svc->getOverrides(),
			'welcome' => $this->svc->getWelcome(),
			'globalPlaceholders' => EmailCatalog::GLOBAL_PLACEHOLDERS,
		]);
	}

	private function describe(string $id, array $entry): array {
		return [
			'id' => $id,
			'label' => $entry['label'],
			'group' => $entry['group'],
			'placeholders' => $entry['placeholders'],
			'essentialBody' => EmailCatalog::bodyCarriesEssentials($id),
			'defaults' => [
				'subject' => $entry['sample']['subject'] ?? '',
				'heading' => $entry['sample']['heading'] ?? '',
				'body' => $entry['sample']['body'] ?? '',
				'button' => $entry['sample']['button'] ?? '',
			],
		];
	}

	public function saveOverride(string $emailId, array $override = []): JSONResponse {
		if ($this->admin() === null) {
			return $this->denied();
		}
		try {
			return new JSONResponse(['override' => $this->svc->saveOverride($emailId, $override)]);
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}

	public function resetOverride(string $emailId): JSONResponse {
		if ($this->admin() === null) {
			return $this->denied();
		}
		$this->svc->deleteOverride($emailId);
		return new JSONResponse(['ok' => true]);
	}

	public function saveWelcome(array $welcome = []): JSONResponse {
		if ($this->admin() === null) {
			return $this->denied();
		}
		try {
			return new JSONResponse(['welcome' => $this->svc->saveWelcome($welcome)]);
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}

	public function uploadImage(): JSONResponse {
		if ($this->admin() === null) {
			return $this->denied();
		}
		$file = $this->request->getUploadedFile('file');
		if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
			return new JSONResponse(['error' => 'Upload failed.'], Http::STATUS_BAD_REQUEST);
		}
		try {
			$stored = $this->svc->storeImage($file['tmp_name']);
			return new JSONResponse($stored);
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}

	#[NoCSRFRequired]
	public function image(string $id): Response {
		if ($this->admin() === null) {
			return $this->denied();
		}
		$image = $this->svc->loadImage($id);
		if ($image === null) {
			return new JSONResponse(['error' => 'Not found.'], Http::STATUS_NOT_FOUND);
		}
		$response = new DataDisplayResponse($image['content'], Http::STATUS_OK, ['Content-Type' => $image['mime']]);
		$response->cacheFor(86400);
		return $response;
	}

	/**
	 * Renders the email with the SAVED settings and sample data.
	 */
	public function preview(string $emailId): JSONResponse {
		$user = $this->admin();
		if ($user === null) {
			return $this->denied();
		}
		try {
			$template = $this->buildSample($emailId, $user);
			$html = $template->renderHtml();
			if ($template instanceof EMailTemplate) {
				foreach ($template->getInlineImages() as $img) {
					$html = str_replace('cid:' . $img['name'], 'data:' . $img['mimeType'] . ';base64,' . base64_encode($img['content']), $html);
				}
			}
			return new JSONResponse([
				'subject' => $template->renderSubject(),
				'html' => $html,
				'text' => $template->renderText(),
				'sizeKb' => (int)round(strlen($html) / 1024),
			]);
		} catch (Throwable $e) {
			return new JSONResponse(['error' => 'Preview failed: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	public function sendTest(string $emailId, string $to = ''): JSONResponse {
		$user = $this->admin();
		if ($user === null) {
			return $this->denied();
		}
		$to = trim($to) !== '' ? trim($to) : (string)$user->getEMailAddress();
		if (!$this->mailer->validateMailAddress($to)) {
			return new JSONResponse(['error' => 'Enter a valid email address (your account has none set).'], Http::STATUS_BAD_REQUEST);
		}

		if ($emailId === EmailCatalog::WELCOME) {
			$result = $this->welcome->send($user->getUID(), $to, true, false);
			return new JSONResponse($result, $result['ok'] ? Http::STATUS_OK : Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		try {
			$template = $this->buildSample($emailId, $user);
			$message = $this->mailer->createMessage();
			$message->setTo([$to]);
			$message->useTemplate($template);
			$failed = $this->mailer->send($message);
			if ($failed !== []) {
				return new JSONResponse(['ok' => false, 'to' => $to, 'error' => 'Sending failed - Nextcloud logs the exact SMTP error in nextcloud.log.'], Http::STATUS_UNPROCESSABLE_ENTITY);
			}
			return new JSONResponse(['ok' => true, 'to' => $to, 'error' => '']);
		} catch (Throwable $e) {
			return new JSONResponse(['ok' => false, 'to' => $to, 'error' => $e->getMessage()], Http::STATUS_UNPROCESSABLE_ENTITY);
		}
	}

	/**
	 * Builds the email the way Nextcloud does: the real welcome email via the
	 * Settings app helper, or a faithful sample sequence for the rest (their
	 * senders can't be triggered on demand).
	 */
	private function buildSample(string $emailId, IUser $user): IEMailTemplate {
		if ($emailId === EmailCatalog::WELCOME) {
			$helper = Server::get('OCA\\Settings\\Mailer\\NewUserMailHelper');
			return $helper->generateTemplate($user, false);
		}

		$entry = EmailCatalog::get($emailId) ?? EmailCatalog::generic($emailId);
		$sample = $entry['sample'];
		$globals = $this->svc->placeholderValues([]);
		$data = [];
		foreach ($sample['data'] ?? [] as $k => $v) {
			$data[$k] = $this->svc->fill((string)$v, $globals);
		}

		$template = $this->mailer->createEMailTemplate($emailId, $data);
		$template->setSubject($this->svc->fill($sample['subject'] ?? '', $globals));
		$template->addHeader();
		$template->addHeading($this->svc->fill($sample['heading'] ?? '', $globals));
		if (($sample['body'] ?? '') !== '') {
			$template->addBodyText($this->svc->fill($sample['body'], $globals));
		}
		if (($sample['button'] ?? '') !== '') {
			$template->addBodyButton($this->svc->fill($sample['button'], $globals), $data['link'] ?? $globals['instance_url']);
		}
		$template->addFooter();
		return $template;
	}
}
