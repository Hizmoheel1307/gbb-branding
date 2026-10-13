<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Mail;

use OC\Mail\EMailTemplate;
use OCP\IConfig;
use OCP\Server;
use Throwable;

/**
 * Overrides only the footer. Logo and header background color are NOT
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

	public function addFooter(string $text = '', ?string $lang = null): void {
		if ($this->footerAdded) {
			return;
		}

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
