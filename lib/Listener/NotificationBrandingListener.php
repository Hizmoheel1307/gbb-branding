<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Listener;

use OCA\GovMailBranding\Service\NotificationBrandingService;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use Throwable;

/**
 * Loads the notification-branding script (js/govmailbranding-notifications.js)
 * on every signed-in page, with its rules as initial state.
 *
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class NotificationBrandingListener implements IEventListener {
	private static bool $added = false;

	public function __construct(
		private NotificationBrandingService $service,
		private IInitialState $initialState,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeTemplateRenderedEvent || !$event->isLoggedIn() || self::$added) {
			return;
		}
		try {
			$cfg = $this->service->forPage();
			if (!$cfg['enabled']) {
				return;
			}
			self::$added = true;
			$this->initialState->provideInitialState('notification_branding', $cfg);
			Util::addScript('govmailbranding', 'govmailbranding-notifications');
		} catch (Throwable) {
			// Branding must never break a page.
		}
	}
}
