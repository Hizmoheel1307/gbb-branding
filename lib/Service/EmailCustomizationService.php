<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Service;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Defaults;

/**
 * Stores and validates everything the Email Templates tab edits:
 * - per-email text overrides (subject, heading, body, buttons)
 * - the designed welcome email (sections with images)
 * - email images, kept in app data so every app server sees them
 *
 * Also owns placeholder rendering and the small formatting syntax admins
 * can use in text fields: **bold**, [link text](https://url), new lines.
 */
class EmailCustomizationService {
	public const APP_ID = 'govmailbranding';
	private const OVERRIDES_KEY = 'email_overrides';
	private const WELCOME_KEY = 'email_welcome';
	private const SEEN_KEY = 'email_seen_ids';
	private const IMAGE_FOLDER = 'email-images';

	public const BODY_MODES = ['keep', 'before', 'after', 'replace'];
	public const LAYOUTS = ['stacked', 'image-left', 'image-right', 'steps'];
	/** Icons shipped with the app (img/email/*.png), referenced as "p-<name>". */
	private const PRESET_DIR = __DIR__ . '/../../img/email';
	public const IMAGE_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif'];
	public const MAX_IMAGE_BYTES = 1024 * 1024;
	private const MAX_TEXT = 4000;
	private const MAX_SECTIONS = 15;
	private const MAX_ITEMS = 8;

	/** @var array<string, bool> per-request cache of recorded IDs */
	private static array $seenThisRequest = [];

	public function __construct(
		private IConfig $config,
		private IAppData $appData,
		private Defaults $defaults,
		private IURLGenerator $urlGenerator,
	) {
	}

	// ---------------------------------------------------------------- overrides

	public function getOverrides(): array {
		return $this->readJson(self::OVERRIDES_KEY);
	}

	public function getOverride(string $emailId): ?array {
		$all = $this->getOverrides();
		$o = $all[$emailId] ?? null;
		return (is_array($o) && ($o['enabled'] ?? false)) ? $o : null;
	}

	public function saveOverride(string $emailId, array $input): array {
		if (!preg_match('/^[A-Za-z0-9_.\-]{1,100}$/', $emailId) || $emailId === EmailCatalog::WELCOME) {
			throw new \InvalidArgumentException('Invalid email ID.');
		}
		$clean = [
			'enabled' => (bool)($input['enabled'] ?? false),
			'subject' => $this->text($input['subject'] ?? '', 300),
			'heading' => $this->text($input['heading'] ?? '', 300),
			'body_mode' => in_array((string)($input['body_mode'] ?? 'keep'), self::BODY_MODES, true) ? (string)$input['body_mode'] : 'keep',
			'body' => $this->text($input['body'] ?? ''),
			'button' => $this->text($input['button'] ?? '', 100),
			'hide_secondary_button' => (bool)($input['hide_secondary_button'] ?? false),
		];
		$all = $this->getOverrides();
		$all[$emailId] = $clean;
		$this->writeJson(self::OVERRIDES_KEY, $all);
		return $clean;
	}

	public function deleteOverride(string $emailId): void {
		$all = $this->getOverrides();
		unset($all[$emailId]);
		$this->writeJson(self::OVERRIDES_KEY, $all);
	}

	// ------------------------------------------------------------------ welcome

	public static function defaultWelcome(): array {
		return [
			'enabled' => false,
			'first_login' => false,
			'login_as_email' => true,
			'subject' => 'Welcome to {instance_name}',
			'heading' => 'Welcome {displayname}',
			'intro' => 'Your {instance_name} account is ready. Here is a quick tour of what you can do.',
			'button' => 'Open {instance_name}',
			'secondary_button' => 'hide',
			'secondary_text' => '',
			'secondary_url' => '',
			'closing' => '',
			'accent' => '#1B8018',
			'title_color' => '#14451A',
			'sections' => [],
		];
	}

	public function getWelcome(): array {
		return array_merge(self::defaultWelcome(), $this->readJson(self::WELCOME_KEY));
	}

	public function saveWelcome(array $input): array {
		$sections = [];
		foreach (array_slice((array)($input['sections'] ?? []), 0, self::MAX_SECTIONS) as $s) {
			if (!is_array($s)) {
				continue;
			}
			$items = [];
			foreach (array_slice((array)($s['items'] ?? []), 0, self::MAX_ITEMS) as $it) {
				if (!is_array($it)) {
					continue;
				}
				$items[] = [
					'icon' => $this->imageId($it['icon'] ?? ''),
					'title' => $this->text($it['title'] ?? '', 120),
					'text' => $this->text($it['text'] ?? '', 600),
				];
			}
			$sections[] = [
				'id' => preg_match('/^[a-z0-9]{1,16}$/', (string)($s['id'] ?? '')) ? $s['id'] : bin2hex(random_bytes(4)),
				'bg' => $this->color($s['bg'] ?? '', '#f4f6fb'),
				'layout' => in_array($s['layout'] ?? '', self::LAYOUTS, true) ? $s['layout'] : 'stacked',
				'columns' => in_array((int)($s['columns'] ?? 2), [2, 3, 4], true) ? (int)($s['columns'] ?? 2) : 2,
				'icon' => $this->imageId($s['icon'] ?? ''),
				'title' => $this->text($s['title'] ?? '', 200),
				'text' => $this->text($s['text'] ?? '', 2000),
				'image' => $this->imageId($s['image'] ?? ''),
				'items' => $items,
			];
		}

		$secondary = (string)($input['secondary_button'] ?? 'hide');
		if (!in_array($secondary, ['hide', 'default', 'custom'], true)) {
			$secondary = 'hide';
		}
		$secondaryUrl = trim((string)($input['secondary_url'] ?? ''));
		if ($secondaryUrl !== '' && !preg_match('#^https?://#i', $secondaryUrl)) {
			throw new \InvalidArgumentException('Second button link must start with https://');
		}

		$clean = [
			'enabled' => (bool)($input['enabled'] ?? false),
			'first_login' => (bool)($input['first_login'] ?? false),
			'login_as_email' => (bool)($input['login_as_email'] ?? true),
			'subject' => $this->text($input['subject'] ?? '', 300),
			'heading' => $this->text($input['heading'] ?? '', 300),
			'intro' => $this->text($input['intro'] ?? ''),
			'button' => $this->text($input['button'] ?? '', 100),
			'secondary_button' => $secondary,
			'secondary_text' => $this->text($input['secondary_text'] ?? '', 100),
			'secondary_url' => $secondaryUrl,
			'closing' => $this->text($input['closing'] ?? ''),
			'accent' => $this->color($input['accent'] ?? '', '#1B8018'),
			'title_color' => $this->color($input['title_color'] ?? '', '#14451A'),
			'sections' => $sections,
		];
		$this->writeJson(self::WELCOME_KEY, $clean);
		return $clean;
	}

	// ------------------------------------------------------------- seen emails

	/**
	 * Remembers every email ID that passes through the template, so the admin
	 * tab can offer emails from apps we don't know about (Mail, Talk, Deck...).
	 */
	public function recordSeen(string $emailId): void {
		if ($emailId === '' || isset(self::$seenThisRequest[$emailId]) || str_starts_with($emailId, 'govmailbranding.')) {
			return;
		}
		self::$seenThisRequest[$emailId] = true;
		$seen = $this->readJson(self::SEEN_KEY);
		if (!isset($seen[$emailId]) && count($seen) < 200 && preg_match('/^[A-Za-z0-9_.\-]{1,100}$/', $emailId)) {
			$seen[$emailId] = gmdate('c');
			$this->writeJson(self::SEEN_KEY, $seen);
		}
	}

	public function getSeen(): array {
		return $this->readJson(self::SEEN_KEY);
	}

	// ------------------------------------------------------------------ images

	/**
	 * @return array{id: string, mime: string, size: int}
	 */
	public function storeImage(string $tmpPath): array {
		$size = (int)filesize($tmpPath);
		if ($size <= 0 || $size > self::MAX_IMAGE_BYTES) {
			throw new \InvalidArgumentException('Images must be under 1 MB (email clients and mail servers penalise large messages).');
		}
		$mime = (string)mime_content_type($tmpPath);
		if (!isset(self::IMAGE_TYPES[$mime])) {
			throw new \InvalidArgumentException('Use PNG, JPEG or GIF. Many email clients cannot show SVG or WebP.');
		}
		$id = bin2hex(random_bytes(8));
		$this->imageFolder()->newFile($id . '.' . self::IMAGE_TYPES[$mime], (string)file_get_contents($tmpPath));
		return ['id' => $id, 'mime' => $mime, 'size' => $size];
	}

	/**
	 * @return array{content: string, mime: string}|null
	 */
	public function loadImage(string $id): ?array {
		if ($this->imageId($id) === '') {
			return null;
		}
		if (str_starts_with($id, 'p-')) {
			$path = self::PRESET_DIR . '/' . substr($id, 2) . '.png';
			return is_file($path) ? ['content' => (string)file_get_contents($path), 'mime' => 'image/png'] : null;
		}
		try {
			$folder = $this->imageFolder();
			foreach (self::IMAGE_TYPES as $mime => $ext) {
				if ($folder->fileExists($id . '.' . $ext)) {
					return ['content' => $folder->getFile($id . '.' . $ext)->getContent(), 'mime' => $mime];
				}
			}
		} catch (\Throwable) {
		}
		return null;
	}

	// --------------------------------------------------------------- rendering

	/**
	 * Values available to placeholders for one email.
	 *
	 * @return array<string, string>
	 */
	public function placeholderValues(array $data, array $extra = []): array {
		$values = [
			'instance_name' => $this->defaults->getName(),
			'instance_url' => rtrim($this->urlGenerator->getAbsoluteURL('/'), '/'),
		];
		foreach ($data + $extra as $key => $value) {
			if (!is_string($key) || !preg_match('/^[A-Za-z0-9_]+$/', $key)) {
				continue;
			}
			if ($value instanceof \DateTimeInterface) {
				$values[$key] = $value->format('j F Y');
			} elseif (is_scalar($value) || $value === null) {
				$values[$key] = is_bool($value) ? ($value ? 'yes' : 'no') : (string)$value;
			}
		}
		if (!isset($values['instancename'])) {
			$values['instancename'] = $values['instance_name'];
		}
		return $values;
	}

	/** Plain substitution, for subjects and plain-text parts. */
	public function fill(string $text, array $values): string {
		return (string)preg_replace_callback('/\{([A-Za-z0-9_]+)\}/', function ($m) use ($values) {
			if (!array_key_exists($m[1], $values)) {
				return $m[0];
			}
			// Sample data may itself reference {instance_url}
			return str_replace(['{instance_url}', '{instance_name}'], [$values['instance_url'], $values['instance_name']], $values[$m[1]]);
		}, $text);
	}

	/** Admin text -> safe HTML: escape, then **bold**, [text](url), new lines. */
	public function toHtml(string $text, array $values): string {
		$html = htmlspecialchars($this->fill($text, $values), ENT_QUOTES);
		$html = (string)preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
		$html = (string)preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', static function ($m) {
			// $m[2] is already HTML-escaped by htmlspecialchars above
			return '<a href="' . $m[2] . '" style="color:inherit;text-decoration:underline;">' . $m[1] . '</a>';
		}, $html);
		return nl2br($html, false);
	}

	public function toPlain(string $text, array $values): string {
		$plain = $this->fill($text, $values);
		$plain = (string)preg_replace('/\*\*(.+?)\*\*/s', '$1', $plain);
		return (string)preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '$1 ($2)', $plain);
	}

	/** @return list<string> ids of the icons bundled with the app */
	public function presetImages(): array {
		$ids = [];
		foreach (glob(self::PRESET_DIR . '/*.png') ?: [] as $file) {
			$ids[] = 'p-' . basename($file, '.png');
		}
		return $ids;
	}

	// ----------------------------------------------------------------- helpers

	private function imageFolder(): ISimpleFolder {
		try {
			return $this->appData->getFolder(self::IMAGE_FOLDER);
		} catch (NotFoundException) {
			return $this->appData->newFolder(self::IMAGE_FOLDER);
		}
	}

	private function imageId(mixed $id): string {
		$id = (string)$id;
		return preg_match('/^([a-f0-9]{16}|p-[a-z0-9-]{1,40})$/', $id) ? $id : '';
	}

	private function color(mixed $c, string $default): string {
		$c = trim((string)$c);
		return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : $default;
	}

	private function text(mixed $t, int $max = self::MAX_TEXT): string {
		$t = str_replace("\r\n", "\n", (string)$t);
		return mb_substr(trim($t), 0, $max);
	}

	private function readJson(string $key): array {
		$raw = $this->config->getAppValue(self::APP_ID, $key, '');
		$decoded = $raw === '' ? [] : json_decode($raw, true);
		return is_array($decoded) ? $decoded : [];
	}

	private function writeJson(string $key, array $value): void {
		$this->config->setAppValue(self::APP_ID, $key, (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}
