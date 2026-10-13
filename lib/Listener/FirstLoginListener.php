<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Listener;

use OCA\GovMailBranding\Service\EmailCustomizationService;
use OCA\GovMailBranding\Service\WelcomeDispatcher;
use OCA\GovMailBranding\Service\WelcomeMailService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserFirstTimeLoggedInEvent;
use Throwable;

/**
 * Sends the welcome email the first time a user ever signs in (GovMail
 * users sign in with their mail password, so no admin "create user" step
 * ever sends Nextcloud's welcome email).
 *
 * @template-implements IEventListener<UserFirstTimeLoggedInEvent>
 */
class FirstLoginListener implements IEventListener {
	public function __construct(
		private EmailCustomizationService $customization,
		private WelcomeMailService $welcome,
		private WelcomeDispatcher $dispatcher,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof UserFirstTimeLoggedInEvent) {
			return;
		}
		try {
			$settings = $this->customization->getWelcome();
			if (!$settings['first_login']) {
				return;
			}
			$uid = $event->getUser()->getUID();
			if ($this->welcome->alreadySent($uid)) {
				return;
			}
			$this->dispatcher->dispatch($uid, (bool)($settings['send_now'] ?? true));
		} catch (Throwable) {
			// Never let the welcome email interfere with logging in.
		}
	}
}
