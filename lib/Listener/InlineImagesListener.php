<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Listener;

use OCA\GovMailBranding\Mail\BrandedEmailTemplate;
use OCA\GovMailBranding\Service\EmailCustomizationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Mail\Events\BeforeMessageSent;
use Throwable;

/**
 * Attaches the welcome-email images (icons, screenshots) as inline parts
 * right before an email is sent.
 *
 * Nextcloud 34.0.1's Message::useTemplate() only copies the HTML and text,
 * so images referenced as cid:gmb<n>-<id> would show as broken. Newer
 * releases attach template images themselves; then this does nothing.
 *
 * @template-implements IEventListener<BeforeMessageSent>
 */
class InlineImagesListener implements IEventListener {
	public function __construct(
		private EmailCustomizationService $svc,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeMessageSent || BrandedEmailTemplate::nativeInlineImages()) {
			return;
		}
		try {
			$message = $event->getMessage();
			if (!method_exists($message, 'getSymfonyEmail') || !method_exists($message, 'attachInline')) {
				return;
			}
			$html = $message->getSymfonyEmail()->getHtmlBody();
			if (!is_string($html) || !str_contains($html, 'cid:gmb')) {
				return;
			}
			preg_match_all('/cid:(gmb\d+-([a-z0-9-]{1,48}))/', $html, $matches, PREG_SET_ORDER);
			$done = [];
			foreach ($matches as [, $name, $id]) {
				if (isset($done[$name])) {
					continue;
				}
				$done[$name] = true;
				$image = $this->svc->loadImage($id);
				if ($image !== null) {
					$message->attachInline($image['content'], $name, $image['mime']);
				}
			}
		} catch (Throwable) {
			// Missing pictures are better than a failed email.
		}
	}
}
