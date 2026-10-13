<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCA\GovMailBranding\Mail\BrandedEmailTemplate;
use OCP\Defaults;
use OCP\Mail\IMailer;
use Throwable;

/**
 * Sends a real email through Nextcloud's mailer, using exactly the template
 * path every other Nextcloud email uses. "No error in the log" is not proof
 * of branding; this is.
 */
class TestEmailService {
	public function __construct(
		private IMailer $mailer,
		private Defaults $defaults,
		private EmailTemplateGuard $guard,
	) {
	}

	public function send(string $to, string $triggeredBy = 'web'): array {
		$to = trim($to);
		$result = [
			'ok' => false,
			'to' => $to,
			'at' => gmdate('c'),
			'host' => (string)gethostname(),
			'via' => $triggeredBy,
			'templateClass' => '',
			'branded' => false,
			'error' => '',
		];

		if (!$this->mailer->validateMailAddress($to)) {
			$result['error'] = 'Not a valid email address.';
			return $result; // not recorded: nothing was attempted
		}

		try {
			$template = $this->mailer->createEMailTemplate('govmailbranding.test', ['host' => $result['host']]);
			$result['templateClass'] = get_class($template);
			$result['branded'] = $template instanceof BrandedEmailTemplate;

			$name = $this->defaults->getName();
			$template->setSubject($name . ' - email branding test');
			$template->addHeader();
			$template->addHeading('Email branding test');
			$template->addBodyText(
				'This is a test email from the GovMail Branding admin panel. '
				. 'If the footer below shows your configured footer text, signature and social links, branding is working.'
			);
			$template->addBodyText('Sent from app server ' . $result['host'] . ' at ' . $result['at'] . ' (UTC).');
			$template->addFooter();

			$message = $this->mailer->createMessage();
			$message->setTo([$to]);
			$message->useTemplate($template);

			$failed = $this->mailer->send($message);
			if ($failed !== []) {
				$result['error'] = 'Sending failed for ' . implode(', ', $failed) . '. Nextcloud logs the exact SMTP error in nextcloud.log (search for "Sending mail to").';
			} else {
				$result['ok'] = true;
			}
		} catch (Throwable $e) {
			$result['error'] = get_class($e) . ': ' . $e->getMessage();
		}

		$this->guard->recordTest($result);
		return $result;
	}
}
