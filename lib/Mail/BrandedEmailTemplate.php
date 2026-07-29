<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Mail;

use OC\Mail\EMailTemplate;

/**
 * Overrides only the footer. Logo and header background color are NOT
 * touched here - Nextcloud's base EMailTemplate::addHeader() already pulls
 * those from the Theming app's logo/primary_color, which our Theming tab
 * already writes to. Duplicating that here would just be two places
 * setting the same thing.
 *
 * This class must be set as 'mail_template_class' in config.php to take
 * effect (done automatically by LoginPageListener... see
 * Application::register()). Known risk: see
 * https://github.com/nextcloud/server/issues/45392 - custom template
 * classes in an app namespace have sometimes failed to load depending on
 * autoloader timing. Always verify with a real test email after deploying,
 * not just "no errors in the log".
 */
class BrandedEmailTemplate extends EMailTemplate {
	public function addFooter($text = '', $lang = null) {
		if ($this->footerAdded) {
			return;
		}

		$hidePromoBanner = $this->getBoolConfig('email_hide_promo_banner');
		$hideCommunityFooter = $this->getBoolConfig('email_hide_community_footer');
		$hideSocialLinks = $this->getBoolConfig('email_hide_social_links');

		$configuredText = trim((string)$this->getConfiguredFooterText());
		$signature = trim((string)$this->getConfiguredSignature());

		// Base text priority: our configured footer text first; otherwise
		// fall back to whatever the calling app passed in, unless the promo
		// banner is hidden, in which case we never use the caller's text.
		if ($configuredText !== '') {
			$baseText = $configuredText;
		} elseif (!$hidePromoBanner && trim((string)$text) !== '') {
			$baseText = (string)$text;
		} else {
			$baseText = '';
		}

		if ($signature !== '') {
			$baseText = $baseText !== '' ? $baseText . '<br>' . $signature : $signature;
		}

		$finalText = $baseText;
		if ($finalText === '' && $hideCommunityFooter) {
			// parent::addFooter() treats an empty string as "use my own
			// built-in default line" - passing a harmless non-empty value
			// blocks that default from ever appearing.
			$finalText = '&nbsp;';
		}

		parent::addFooter($finalText, $lang);

		if (!$hideSocialLinks) {
			$socialLinksHtml = $this->buildSocialLinksHtml();
			if ($socialLinksHtml !== '') {
				// Append social links after the standard footer content, inside
				// the same closing structure parent::addFooter() already built.
				$this->htmlBody = str_replace(
					'</body>',
					'<div style="text-align:center;padding:8px 0;">' . $socialLinksHtml . '</div></body>',
					$this->htmlBody
				);
			}
		}
	}

	private function getBoolConfig(string $key): bool {
		return \OC::$server->getConfig()->getAppValue('govmailbranding', $key, '') === '1';
	}

	private function getConfiguredFooterText(): string {
		return \OC::$server->getConfig()->getAppValue('govmailbranding', 'email_footer', '');
	}

	private function getConfiguredSignature(): string {
		return \OC::$server->getConfig()->getAppValue('govmailbranding', 'email_signature', '');
	}

	/**
	 * Social links are stored as one "Label, https://url" per line.
	 */
	private function buildSocialLinksHtml(): string {
		$raw = \OC::$server->getConfig()->getAppValue('govmailbranding', 'email_social_links', '');
		if (trim($raw) === '') {
			return '';
		}

		$links = [];
		foreach (explode("\n", $raw) as $line) {
			$line = trim($line);
			if ($line === '') {
				continue;
			}
			$parts = array_map('trim', explode(',', $line, 2));
			if (count($parts) === 2 && filter_var($parts[1], FILTER_VALIDATE_URL)) {
				$label = htmlspecialchars($parts[0]);
				$url = htmlspecialchars($parts[1]);
				$links[] = '<a href="' . $url . '" style="margin:0 6px;color:inherit;">' . $label . '</a>';
			}
		}

		return implode(' ', $links);
	}
}
