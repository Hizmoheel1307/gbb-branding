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

		$rules = [];
		if ($hidePromo && $hideCommunity && $hideSocial) {
			// All three checked - hide the whole confirmed block outright,
			// the most reliable option since this exact class is
			// independently documented by the Nextcloud community.
			$rules[] = '.development-notice { display: none !important; }';
			$hidePromo = $hideCommunity = $hideSocial = false;
		}
		if ($hidePromo) {
			$rules[] = '#open-reasons-use-nextcloud-pdf { display: none !important; }';
		}
		if ($hideCommunity) {
			$rules[] = '.development-notice > p { display: none !important; }';
		}
		if ($hideSocial) {
			$rules[] = '.development-notice .social-icons, .development-notice ul { display: none !important; }';
		}

		// Mail app fixes (on unless switched off)
		if ($this->config->getAppValue('govmailbranding', 'fix_mail_preview', '1') !== '0') {
			// Mail caps the 2-line preview at a fixed 45px; with larger text
			// (phone text size, browser zoom) the second line is cut in half.
			$rules[] = '.envelope .envelope__preview-text { max-height: none !important; }';
		}
		if ($this->config->getAppValue('govmailbranding', 'fix_mail_touch_actions', '1') !== '0') {
			// The read/important/delete buttons appear on mouse hover; on a phone a
			// tap leaves the row "hovered" and the buttons cover the sender.
			$rules[] = '@media (hover: none) { .envelope .list-item-content__quick-actions { display: none !important; } }';
		}

		if ($this->config->getAppValue('govmailbranding', 'fix_mail_subject', '1') !== '0') {
			$rules[] = self::subjectCss(
				$this->config->getAppValue('govmailbranding', 'mail_subject_size_desktop', ''),
				$this->config->getAppValue('govmailbranding', 'mail_subject_size_mobile', ''),
				$this->config->getAppValue('govmailbranding', 'mail_subject_lines_mobile', ''),
			);
		}

		$custom = self::sanitizeCss($this->config->getAppValue('govmailbranding', 'custom_css', ''));
		if ($custom !== '') {
			$rules[] = "/* GovMail Branding: custom CSS */\n" . $custom;
		}

		return implode("\n", $rules);
	}

	public const SUBJECT_DEFAULTS = ['desktop' => 20, 'mobile' => 18, 'lines' => 2];

	/**
	 * Size of the subject heading above an opened message in Mail
	 * (#mail-thread-header-fields h2). Mail inherits Nextcloud's large h2,
	 * which on phones wraps over 3-4 lines and fills the screen. Mail
	 * switches to its phone/tablet layout at 1024px, so the same breakpoint
	 * is used here. lines = 0 means no limit.
	 */
	public static function subjectCss(string $desktop, string $mobile, string $lines): string {
		$d = self::intIn($desktop, 12, 40, self::SUBJECT_DEFAULTS['desktop']);
		$m = self::intIn($mobile, 12, 40, self::SUBJECT_DEFAULTS['mobile']);
		$l = self::intIn($lines, 0, 6, self::SUBJECT_DEFAULTS['lines']);
		$css = '#mail-thread-header-fields h2 { font-size: ' . $d . 'px !important; line-height: 1.3 !important; }';
		// Mail pads the bottom of the h2; clipped text would show through that
		// padding, so the same space is moved outside as a margin.
		$mobileRules = 'font-size: ' . $m . 'px !important; line-height: 1.3 !important; padding-bottom: 0 !important; margin-bottom: calc(var(--default-grid-baseline, 4px) * 2) !important;';
		if ($l > 0) {
			$mobileRules .= ' display: -webkit-box !important; -webkit-line-clamp: ' . $l . '; -webkit-box-orient: vertical; overflow: hidden !important; white-space: normal !important;';
		}
		return $css . "\n@media only screen and (max-width: 1024px) { #mail-thread-header-fields h2 { " . $mobileRules . ' } }';
	}

	private static function intIn(string $value, int $min, int $max, int $default): int {
		if (trim($value) === '' || !is_numeric($value)) {
			return $default;
		}
		return max($min, min($max, (int)$value));
	}

	/** Keep admin CSS inside its <style> element. */
	public static function sanitizeCss(string $css): string {
		$css = (string)preg_replace('#</?\s*style[^>]*>#i', '', $css);
		$css = str_ireplace(['</', '<!--', '-->'], ['', '', ''], $css);
		return trim(mb_substr($css, 0, 50000));
	}

	private function getBool(string $key): bool {
		return $this->config->getAppValue('govmailbranding', $key, '') === '1';
	}
}
