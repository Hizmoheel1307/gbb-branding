<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCP\IConfig;

/**
 * Bridges our "Theming" settings tab to Nextcloud's own theming app config.
 * These are NOT our own app's settings - writing here directly changes
 * what OCA\Theming\ThemingDefaults serves across the whole instance,
 * since that class reads these exact same appconfig keys under the
 * 'theming' app id.
 *
 * Image fields (logo, header logo, favicon, background) are intentionally
 * NOT handled here yet - Theming stores those via its own ImageManager
 * service (file storage + mime tracking + cachebuster), which needs
 * dedicated integration rather than a plain config write. See the
 * "Theming tab: image fields" follow-up issue.
 */
class ThemingBridgeService {
	private const THEMING_APP_ID = 'theming';

	/**
	 * Maps our field name to Nextcloud theming's actual appconfig key.
	 */
	private const FIELD_MAP = [
		'theming_application_name' => 'name',
		'theming_web_link' => 'url',
		'theming_slogan' => 'slogan',
		'theming_legal_notice' => 'imprintUrl',
		'theming_privacy_policy' => 'privacyUrl',
	];

	public function __construct(
		private IConfig $config,
	) {
	}

	public function getAll(): array {
		$values = [];
		foreach (self::FIELD_MAP as $ourKey => $themingKey) {
			$values[$ourKey] = $this->config->getAppValue(self::THEMING_APP_ID, $themingKey, '');
		}
		return $values;
	}

	/**
	 * @param array<string, string> $values
	 * @return array<string, string> the values actually accepted/saved
	 */
	public function setMany(array $values): array {
		$saved = [];
		foreach ($values as $ourKey => $value) {
			if (!isset(self::FIELD_MAP[$ourKey])) {
				continue; // ignore unknown keys
			}
			$themingKey = self::FIELD_MAP[$ourKey];
			$this->config->setAppValue(self::THEMING_APP_ID, $themingKey, (string)$value);
			$saved[$ourKey] = (string)$value;
		}
		return $saved;
	}

	public function isKnownKey(string $key): bool {
		return isset(self::FIELD_MAP[$key]);
	}
}
