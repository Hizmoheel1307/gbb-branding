<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCP\IConfig;

class BrandingConfigService {
	private const APP_ID = 'govmailbranding';

	/**
	 * Whitelist of every setting key this app persists, grouped by tab.
	 * Keys not in this list are silently ignored by set() to avoid
	 * arbitrary appconfig writes from a malformed request.
	 * Image fields store a filename (see MediaController) not raw data.
	 */
	private const KEYS = [
		// General Branding (our own data, not tied to Nextcloud core)
		'general_portal_name', 'general_company_name', 'general_support_email',
		'general_support_phone', 'general_website', 'general_footer_text',

		// Login Branding
		'login_title', 'login_subtitle', 'login_background', 'login_logo',

		// Colors
		'color_primary', 'color_background', 'color_header', 'color_accent',

		// Footer
		'footer_copyright', 'footer_company', 'footer_links', 'footer_version',

		// Email Branding (logo/header color intentionally NOT here - see
		// Theming tab, which already controls what emails inherit)
		'email_footer', 'email_signature', 'email_social_links',
		'email_hide_promo_banner', 'email_hide_community_footer', 'email_hide_social_links',

		// Custom code
		'custom_css', 'custom_js',
	];

	public function __construct(
		private IConfig $config,
	) {
	}

	public function getAll(): array {
		$values = [];
		foreach (self::KEYS as $key) {
			$values[$key] = $this->config->getAppValue(self::APP_ID, $key, '');
		}
		return $values;
	}

	public function get(string $key): ?string {
		if (!in_array($key, self::KEYS, true)) {
			return null;
		}
		return $this->config->getAppValue(self::APP_ID, $key, '');
	}

	/**
	 * @param array<string, string> $values
	 * @return array<string, string> the values actually accepted/saved
	 */
	public function setMany(array $values): array {
		$saved = [];
		foreach ($values as $key => $value) {
			if (!in_array($key, self::KEYS, true)) {
				continue; // ignore unknown keys rather than erroring the whole request
			}
			$this->config->setAppValue(self::APP_ID, $key, (string)$value);
			$saved[$key] = (string)$value;
		}
		return $saved;
	}

	public function isKnownKey(string $key): bool {
		return in_array($key, self::KEYS, true);
	}
}
