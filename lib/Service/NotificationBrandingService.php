<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCP\Defaults;
use OCP\IConfig;
use OCP\IURLGenerator;

/**
 * Settings for browser (desktop) notifications.
 *
 * Nextcloud apps hard-code their notification titles, e.g. the Mail app
 * shows t('mail', 'Nextcloud Mail'). A small script on every signed-in page
 * (src/notifications.js) rewrites the title and icon just before the browser
 * shows the notification, using the rules stored here.
 */
class NotificationBrandingService {
	private const APP_ID = 'govmailbranding';
	private const KEY = 'notification_branding';
	public const ICONS = ['keep', 'mail', 'grid', 'chat', 'calendar', 'bell'];

	public function __construct(
		private IConfig $config,
		private Defaults $defaults,
		private IURLGenerator $urlGenerator,
	) {
	}

	public static function defaults(): array {
		return [
			'enabled' => true,
			'rules' => [
				['from' => 'Nextcloud Mail', 'to' => '{instance_name}'],
			],
			'replace_word' => true,
			'icon' => 'mail',
		];
	}

	public function get(): array {
		$raw = $this->config->getAppValue(self::APP_ID, self::KEY, '');
		$saved = $raw === '' ? [] : json_decode($raw, true);
		return array_merge(self::defaults(), is_array($saved) ? $saved : []);
	}

	public function save(array $input): array {
		$rules = [];
		foreach (array_slice((array)($input['rules'] ?? []), 0, 20) as $rule) {
			if (!is_array($rule)) {
				continue;
			}
			$from = mb_substr(trim((string)($rule['from'] ?? '')), 0, 120);
			$to = mb_substr(trim((string)($rule['to'] ?? '')), 0, 120);
			if ($from !== '') {
				$rules[] = ['from' => $from, 'to' => $to];
			}
		}
		$icon = (string)($input['icon'] ?? 'mail');
		$clean = [
			'enabled' => (bool)($input['enabled'] ?? true),
			'rules' => $rules,
			'replace_word' => (bool)($input['replace_word'] ?? true),
			'icon' => in_array($icon, self::ICONS, true) ? $icon : 'mail',
		];
		$this->config->setAppValue(self::APP_ID, self::KEY, (string)json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		return $clean;
	}

	/**
	 * What the browser script needs: placeholders resolved, icon as a URL.
	 */
	public function forPage(): array {
		$cfg = $this->get();
		$name = $this->defaults->getName();
		$rules = [];
		foreach ($cfg['rules'] as $rule) {
			$rules[] = ['from' => $rule['from'], 'to' => str_replace('{instance_name}', $name, $rule['to'])];
		}
		return [
			'enabled' => $cfg['enabled'],
			'rules' => $rules,
			'replaceWord' => $cfg['replace_word'],
			'name' => $name,
			'icon' => $cfg['icon'] === 'keep' ? '' : $this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->imagePath(self::APP_ID, 'email/app-' . $cfg['icon'] . '.png')
			),
		];
	}
}
