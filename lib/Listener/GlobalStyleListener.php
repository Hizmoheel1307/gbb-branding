<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Listener;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Util;

/**
 * Hides Nextcloud's own "Reasons to use Nextcloud in your organisation"
 * button, community credit line, and social icons on Personal Settings.
 *
 * Selectors used:
 * - #open-reasons-use-nextcloud-pdf (the promo button) and
 *   .development-notice (the whole block) are independently confirmed by
 *   multiple reports in the Nextcloud community forum as stable across
 *   versions: https://help.nextcloud.com/t/104750
 * - The finer split below (credit text vs social icons specifically) is
 *   a best-effort selector, NOT independently confirmed. Verify visually
 *   after deploying; if a partial hide doesn't isolate correctly, checking
 *   all three boxes falls back to hiding the entire confirmed block.
 *
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class GlobalStyleListener implements IEventListener {
	public function __construct(
		private IConfig $config,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeTemplateRenderedEvent) {
			return;
		}

		// This banner only appears on authenticated pages (Personal
		// Settings), nothing to do on the guest/login screen.
		if (!$event->isLoggedIn()) {
			return;
		}

		$css = $this->buildCss();
		if ($css !== '') {
			Util::addHeader('style', [], $css);
		}
	}

	private function buildCss(): string {
		$hidePromo = $this->getBool('email_hide_promo_banner');
		$hideCommunity = $this->getBool('email_hide_community_footer');
		$hideSocial = $this->getBool('email_hide_social_links');

		if ($hidePromo && $hideCommunity && $hideSocial) {
			// All three checked - hide the whole confirmed block outright,
			// the most reliable option since this exact class is
			// independently documented by the Nextcloud community.
			return '.development-notice { display: none !important; }';
		}

		$rules = [];
		if ($hidePromo) {
			$rules[] = '#open-reasons-use-nextcloud-pdf { display: none !important; }';
		}
		if ($hideCommunity) {
			$rules[] = '.development-notice > p { display: none !important; }';
		}
		if ($hideSocial) {
			$rules[] = '.development-notice .social-icons, .development-notice ul { display: none !important; }';
		}

		return implode("\n", $rules);
	}

	private function getBool(string $key): bool {
		return $this->config->getAppValue('govmailbranding', $key, '') === '1';
	}
}
