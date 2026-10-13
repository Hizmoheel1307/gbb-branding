<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Mail;

use OC\Mail\EMailTemplate;
use OCA\GovMailBranding\Service\EmailCatalog;
use OCA\GovMailBranding\Service\EmailCustomizationService;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Server;
use Throwable;

/**
 * Branded footer + per-email customisation. Logo and header background color are NOT
 * touched here - Nextcloud's base EMailTemplate::addHeader() already pulls
 * those from the Theming app's logo/primary_color, which our Theming tab
 * already writes to.
 *
 * Activated by pointing 'mail_template_class' at this class. That is done
 * ONLY by EmailTemplateGuard after a render smoke test passes - never
 * blindly on every request. Nextcloud's Mailer falls back to the stock
 * template whenever this class can't be autoloaded (e.g. app disabled, or
 * the timing issue in https://github.com/nextcloud/server/issues/45392),
 * so the realistic failure mode is "unbranded", not "broken".
 *
 * IMPORTANT: method signatures here must match OC\Mail\EMailTemplate
 * exactly (types + return types). A mismatch is a PHP compile error the
 * moment the Mailer autoloads this class, which takes down every email
 * send. Re-check after each Nextcloud major upgrade.
 */
class BrandedEmailTemplate extends EMailTemplate {
	public const APP_ID = 'govmailbranding';

	// ------------------------------------------------------------------
	// Per-email customisation (Email Templates tab).
	//
	// Nextcloud builds every email by calling setSubject(), addHeading(),
	// addBodyText(), addBodyButton()... on this object. We intercept those
	// calls and swap in the admin's text. Anything not customised passes
	// through untouched, and any error falls back to the original text:
	// customisation must never stop an email from being sent.
	// ------------------------------------------------------------------

	private bool $resolved = false;
	/** 'none' | 'override' | 'welcome' */
	private string $mode = 'none';
	private array $cfg = [];
	private array $values = [];
	private ?EmailCustomizationService $svc = null;
	private bool $headingReplaced = false;
	private bool $buttonReplaced = false;
	private bool $customBodyDone = false;
	private bool $richDone = false;
	private int $imageCounter = 0;
	/** @var array<string, string> image id => cid */
	private array $embedded = [];

	private function resolve(): void {
		if ($this->resolved) {
			return;
		}
		$this->resolved = true;
		try {
			$this->svc = Server::get(EmailCustomizationService::class);
			$this->svc->recordSeen($this->emailId);

			if ($this->emailId === EmailCatalog::WELCOME) {
				$welcome = $this->svc->getWelcome();
				if ($welcome['enabled']) {
					$this->mode = 'welcome';
					$this->cfg = [
						'subject' => $welcome['subject'],
						'heading' => $welcome['heading'],
						'body_mode' => 'replace',
						'body' => $welcome['intro'],
						'button' => $welcome['button'],
						'welcome' => $welcome,
					];
				}
			} elseif (($override = $this->svc->getOverride($this->emailId)) !== null) {
				$this->mode = 'override';
				$this->cfg = $override;
			}

			if ($this->mode !== 'none') {
				$this->values = $this->svc->placeholderValues($this->data, $this->extraValues());
			}
		} catch (Throwable $e) {
			$this->mode = 'none';
		}
	}

	private function extraValues(): array {
		if ($this->emailId !== EmailCatalog::WELCOME) {
			return [];
		}
		$uid = (string)($this->data['userid'] ?? '');
		$email = '';
		try {
			$user = Server::get(IUserManager::class)->get($uid);
			$email = (string)($user?->getEMailAddress() ?? '');
		} catch (Throwable) {
		}
		if ($email === '' && filter_var($uid, FILTER_VALIDATE_EMAIL)) {
			$email = $uid;
		}
		return ['email' => $email];
	}

	private function cfgText(string $key): string {
		return trim((string)($this->cfg[$key] ?? ''));
	}

	/**
	 * Header with a bigger logo and no coloured circle behind it, plus
	 * tighter spacing. Built by post-processing Nextcloud's own header so the
	 * logo source (URL or embedded) stays whatever this Nextcloud version uses.
	 */
	public function addHeader(): void {
		if ($this->headerAdded) {
			return;
		}
		$this->resolve();
		$header = null;
		try {
			$header = $this->svc?->getHeader();
		} catch (Throwable) {
		}
		if ($header === null || !$header['enabled']) {
			parent::addHeader();
			return;
		}

		if ($header['compact']) {
			$this->compactSpacing();
		}

		$start = strlen($this->htmlBody);
		parent::addHeader();
		try {
			$this->htmlBody = substr($this->htmlBody, 0, $start)
				. self::restyleHeader(substr($this->htmlBody, $start), $header);
		} catch (Throwable) {
			// keep Nextcloud's header as rendered
		}
	}

	/** @internal public for tests */
	public static function restyleHeader(string $html, array $header): string {
		$w = (int)$header['logo_width'];
		$bg = $header['background'] === 'color' ? $header['background_color'] : 'transparent';
		$pad = $header['background'] === 'color' ? '20px 16px' : '24px 0 4px';
		$radius = $header['background'] === 'color' ? 'border-radius:8px;' : '';

		// The logo container is 150px wide by default.
		$html = (string)preg_replace('/(class="container"[^>]*?)width:150px/', '${1}width:' . ($w + 40) . 'px', $html, 1);
		// The coloured circle behind the logo.
		$html = (string)preg_replace('/<center data-parsed="" style="[^"]*">/', '<center data-parsed="" style="background-color:' . $bg . ';padding:' . $pad . ';' . $radius . '">', $html, 1);
		// Logo size: Outlook needs a width attribute, CSS alone is ignored.
		$html = (string)preg_replace_callback('/<img class="logo float-center"[^>]*>/', static function ($m) use ($w) {
			$img = (string)preg_replace('/\s(width|height)="\d+"/', '', $m[0]);
			$img = (string)preg_replace('/max-height:\d+px;max-width:\d+px;width:auto;height:auto/', 'max-width:' . $w . 'px;width:' . $w . 'px;height:auto', $img);
			return str_replace('<img ', '<img width="' . $w . '" ', $img);
		}, $html, 1);
		// Space under the header (Outlook renders the default as ~80px).
		return self::shrinkSpacers($html, 16);
	}

	private function compactSpacing(): void {
		$this->heading = self::shrinkSpacers($this->heading, 12);
		$this->button = self::shrinkSpacers($this->button, 16);
		$this->buttonGroup = self::shrinkSpacers($this->buttonGroup, 16);
		$this->bodyText = str_replace('padding-bottom:30px', 'padding-bottom:14px', $this->bodyText);
	}

	private static function shrinkSpacers(string $tpl, int $px): string {
		return (string)preg_replace(
			'/(<td height=")\d+px("[^>]*?font-size:)\d+px([^>]*?line-height:)\d+px/',
			'${1}' . $px . 'px${2}' . $px . 'px${3}' . $px . 'px',
			$tpl,
		);
	}

	public function setSubject(string $subject): void {
		$this->resolve();
		if ($this->mode !== 'none' && $this->cfgText('subject') !== '') {
			$subject = $this->svc->fill($this->cfgText('subject'), $this->values);
		}
		parent::setSubject($subject);
	}

	public function addHeading(string $title, $plainTitle = ''): void {
		$this->resolve();
		if ($this->mode !== 'none' && !$this->headingReplaced && $this->cfgText('heading') !== '') {
			$this->headingReplaced = true;
			$title = $this->svc->fill($this->cfgText('heading'), $this->values);
			if ($plainTitle !== false) {
				$plainTitle = '';
			}
		}
		parent::addHeading($title, $plainTitle);
	}

	public function addBodyText(string $text, $plainText = ''): void {
		$this->resolve();
		$this->injectCustomBody('start');
		if ($this->mode !== 'none' && ($this->cfg['body_mode'] ?? 'keep') === 'replace') {
			return; // admin replaced the default paragraphs
		}
		parent::addBodyText($text, $plainText);
	}

	public function addBodyListItem(
		string $text,
		string $metaInfo = '',
		string $icon = '',
		$plainText = '',
		$plainMetaInfo = '',
		$plainIndent = 0,
	): void {
		$this->resolve();
		$this->injectCustomBody('start');
		parent::addBodyListItem($text, $metaInfo, $icon, $plainText, $plainMetaInfo, $plainIndent);
	}

	public function addBodyButton(string $text, string $url, $plainText = ''): void {
		$this->resolve();
		$this->injectCustomBody('start');
		[$text, $plainText] = $this->buttonLabel($text, $plainText);
		parent::addBodyButton($text, $url, $plainText);
		$this->appendRichSections();
	}

	public function addBodyButtonGroup(
		string $textLeft,
		string $urlLeft,
		string $textRight,
		string $urlRight,
		string $plainTextLeft = '',
		string $plainTextRight = '',
	): void {
		$this->resolve();
		$this->injectCustomBody('start');
		[$textLeft, $plainTextLeft] = $this->buttonLabel($textLeft, $plainTextLeft);

		$secondary = 'default';
		if ($this->mode === 'welcome') {
			$secondary = $this->cfg['welcome']['secondary_button'] ?? 'hide';
		} elseif ($this->mode === 'override' && ($this->cfg['hide_secondary_button'] ?? false)) {
			$secondary = 'hide';
		}

		if ($secondary === 'hide') {
			parent::addBodyButton($textLeft, $urlLeft, $plainTextLeft);
		} else {
			if ($secondary === 'custom') {
				$w = $this->cfg['welcome'];
				if (trim((string)$w['secondary_text']) !== '') {
					$textRight = $this->svc->fill($w['secondary_text'], $this->values);
					$plainTextRight = '';
				}
				if (trim((string)$w['secondary_url']) !== '') {
					$urlRight = $this->svc->fill($w['secondary_url'], $this->values);
				}
			}
			parent::addBodyButtonGroup($textLeft, $urlLeft, $textRight, $urlRight, $plainTextLeft, $plainTextRight);
		}
		$this->appendRichSections();
	}

	/**
	 * @return array{0: string, 1: string|false}
	 */
	private function buttonLabel(string $text, mixed $plainText): array {
		if ($this->mode === 'none' || $this->buttonReplaced || $this->cfgText('button') === '') {
			return [$text, $plainText];
		}
		$this->buttonReplaced = true;
		$label = $this->svc->fill($this->cfgText('button'), $this->values);
		// parent escapes only when plain text is '' - keep that contract.
		return $plainText === false ? [htmlspecialchars($label, ENT_QUOTES), false] : [$label, ''];
	}

	/**
	 * @param 'start'|'end' $at
	 */
	private function injectCustomBody(string $at): void {
		if ($this->mode === 'none' || $this->customBodyDone || $this->footerAdded) {
			return;
		}
		$mode = $this->cfg['body_mode'] ?? 'keep';
		$body = $this->cfgText('body');
		if ($body === '' || $mode === 'keep') {
			return;
		}
		$wanted = $mode === 'after' ? 'end' : 'start';
		if ($at !== $wanted && !($at === 'end' && $wanted === 'start')) {
			return;
		}
		$this->customBodyDone = true;
		foreach (preg_split('/\n\s*\n/', $body) ?: [] as $paragraph) {
			if (trim($paragraph) === '') {
				continue;
			}
			parent::addBodyText($this->svc->toHtml($paragraph, $this->values), $this->svc->toPlain($paragraph, $this->values));
		}
	}

	private function appendRichSections(): void {
		if ($this->mode !== 'welcome' || $this->richDone || $this->footerAdded) {
			return;
		}
		$this->richDone = true;
		$w = $this->cfg['welcome'];
		try {
			[$html, $plain] = WelcomeRenderer::render(
				$w['sections'] ?? [],
				$this->values,
				$this->svc,
				fn (string $id) => $this->embedImage($id),
				(string)($w['accent'] ?? '#1B8018'),
				(string)($w['title_color'] ?? '#14451A'),
			);
			if (trim((string)$w['closing']) !== '') {
				$html .= vsprintf($this->bodyText, [$this->svc->toHtml($w['closing'], $this->values)]);
				$plain .= $this->svc->toPlain($w['closing'], $this->values) . PHP_EOL . PHP_EOL;
			}
			if ($html !== '') {
				$this->ensureBodyListClosed();
				$this->ensureBodyIsOpened();
				$this->htmlBody .= $html;
				$this->plainBody .= PHP_EOL . $plain;
			}
		} catch (Throwable) {
			// A broken section must not block the welcome email.
		}
	}

	private function embedImage(string $id): ?string {
		if (isset($this->embedded[$id])) {
			return $this->embedded[$id];
		}
		$image = $this->svc->loadImage($id);
		if ($image === null) {
			return null;
		}
		$name = 'gmb' . (++$this->imageCounter) . '-' . $id;
		if (self::nativeInlineImages()) {
			// Newer Nextcloud attaches these itself in Message::useTemplate().
			$this->inlineImages[] = ['name' => $name, 'content' => $image['content'], 'mimeType' => $image['mime']];
		}
		// Otherwise (e.g. Nextcloud 34.0.1) InlineImagesListener attaches every
		// cid:gmb… image right before the message is sent.
		return $this->embedded[$id] = 'cid:' . $name;
	}

	/** Whether this Nextcloud's EMailTemplate/Message embed template images themselves. */
	public static function nativeInlineImages(): bool {
		return method_exists(EMailTemplate::class, 'getInlineImages');
	}

	private function beforeFooter(): void {
		$this->resolve();
		$this->injectCustomBody('end');
		$this->appendRichSections();
	}

	public function addFooter(string $text = '', ?string $lang = null): void {
		if ($this->footerAdded) {
			return;
		}
		$this->beforeFooter();

		try {
			[$html, $plain] = $this->buildFooter($text);
		} catch (Throwable $e) {
			// Branding must never stop an email from being sent.
			parent::addFooter($text, $lang);
			return;
		}

		if ($html === '') {
			// Nothing configured: keep Nextcloud's default footer text.
			parent::addFooter($text, $lang);
			return;
		}

		$plainLength = strlen($this->plainBody);
		parent::addFooter($html, $lang);

		// parent::addFooter() derives the plain-text footer by swapping <br>
		// for newlines, which would leak <a> tags into the text part. Replace
		// what it appended with our own clean plain-text version.
		$this->plainBody = substr($this->plainBody, 0, $plainLength)
			. PHP_EOL . '-- ' . PHP_EOL . $plain;
	}

	/**
	 * Some callers render without ever calling addFooter(). Make sure those
	 * emails still get the branded footer instead of none at all.
	 */
	public function renderHtml(): string {
		if (!$this->footerAdded) {
			$this->addFooter();
		}
		return parent::renderHtml();
	}

	public function renderText(): string {
		if (!$this->footerAdded) {
			$this->addFooter();
		}
		return parent::renderText();
	}

	/**
	 * @return array{0: string, 1: string} [html, plain]; html is '' when
	 *                                      nothing is configured
	 */
	private function buildFooter(string $defaultText): array {
		$footer = trim($this->appValue('email_footer'));
		$signature = trim($this->appValue('email_signature'));
		$links = self::parseSocialLinks($this->appValue('email_social_links'));

		if ($footer === '' && $signature === '' && $links === []) {
			return ['', ''];
		}

		$htmlParts = [];
		$plainParts = [];

		if ($footer !== '') {
			$htmlParts[] = nl2br($footer, false);
			$plainParts[] = self::toPlain($footer);
		} elseif ($defaultText !== '') {
			$htmlParts[] = $defaultText;
			$plainParts[] = self::toPlain($defaultText);
		}

		if ($signature !== '') {
			$htmlParts[] = nl2br($signature, false);
			$plainParts[] = self::toPlain($signature);
		}

		if ($links !== []) {
			$anchors = [];
			$plainLinks = [];
			foreach ($links as [$label, $url]) {
				$anchors[] = '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" style="margin:0 6px;color:#C8C8C8;text-decoration:underline;">'
					. htmlspecialchars($label, ENT_QUOTES) . '</a>';
				$plainLinks[] = $label . ': ' . $url;
			}
			// Inside the footer paragraph, so links sit within the centred
			// email layout rather than after the closing tables.
			$htmlParts[] = implode(' ', $anchors);
			$plainParts[] = implode(PHP_EOL, $plainLinks);
		}

		return [implode('<br>', $htmlParts), implode(PHP_EOL, $plainParts)];
	}

	/**
	 * Social links are stored as one "Label, https://url" per line.
	 *
	 * @return list<array{0: string, 1: string}>
	 */
	public static function parseSocialLinks(string $raw): array {
		$links = [];
		foreach (preg_split('/\R/', $raw) ?: [] as $line) {
			$line = trim($line);
			if ($line === '') {
				continue;
			}
			$parts = array_map('trim', explode(',', $line, 2));
			if (count($parts) === 2 && $parts[0] !== ''
				&& filter_var($parts[1], FILTER_VALIDATE_URL)
				&& preg_match('#^https?://#i', $parts[1])) {
				$links[] = [$parts[0], $parts[1]];
			}
		}
		return $links;
	}

	private static function toPlain(string $html): string {
		$text = preg_replace('#<br\s*/?>#i', PHP_EOL, $html) ?? $html;
		return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
	}

	private function appValue(string $key): string {
		return (string)Server::get(IConfig::class)->getAppValue(self::APP_ID, $key, '');
	}
}
