<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Mail;

use OCA\GovMailBranding\Service\EmailCustomizationService;

/**
 * Turns the welcome-email sections designed in the admin tab into
 * email-client-safe HTML (tables + inline styles only, no CSS classes,
 * no external images) plus a plain-text equivalent.
 *
 * Layouts:
 *  - stacked      icon, title, text, picture, then tiles in 2-4 columns
 *  - image-left   picture beside the text (falls back to stacked without a picture)
 *  - image-right  same, picture on the right
 *  - steps        icon/title/text, then the tiles as numbered steps
 *
 * Images are embedded as inline attachments (cid:) through $addImage, so
 * Outlook/Gmail show them without "load remote images".
 */
final class WelcomeRenderer {
	private const FONT = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif";

	/**
	 * @param callable(string $id): ?string $addImage returns the cid: src, or null if the image is missing
	 * @return array{0: string, 1: string} [html, plain]
	 */
	public static function render(
		array $sections,
		array $values,
		EmailCustomizationService $svc,
		callable $addImage,
		string $accent = '#1B8018',
		string $titleColor = '#14451A',
	): array {
		$html = '';
		$plain = '';

		foreach ($sections as $s) {
			$bg = $s['bg'] ?? '#f4f6fb';
			$dark = self::isDark($bg);
			$c = [
				'title' => $dark ? '#ffffff' : $titleColor,
				'text' => $dark ? '#e8ecf3' : '#4a5568',
				'accent' => $accent,
				'badgeText' => self::isDark($accent) ? '#ffffff' : '#1f2a44',
			];
			$layout = $s['layout'] ?? 'stacked';
			$picture = ($s['image'] ?? '') !== '' ? $addImage($s['image']) : null;
			if (in_array($layout, ['image-left', 'image-right'], true) && $picture === null) {
				$layout = 'stacked';
			}

			$items = array_values(array_filter($s['items'] ?? [], static fn ($i) => ($i['title'] ?? '') !== '' || ($i['text'] ?? '') !== ''));
			$titlePlain = ($s['title'] ?? '') !== '' ? $svc->toPlain($s['title'], $values) : '';
			$textPlain = ($s['text'] ?? '') !== '' ? $svc->toPlain($s['text'], $values) : '';
			if ($titlePlain !== '') {
				$plain .= mb_strtoupper($titlePlain) . PHP_EOL;
			}
			if ($textPlain !== '') {
				$plain .= $textPlain . PHP_EOL;
			}

			if ($layout === 'image-left' || $layout === 'image-right') {
				$textCell = self::heading($s, $values, $svc, $addImage, $c, 'left')
					. self::tilesList($items, $values, $svc, $addImage, $c);
				$imgCell = '<img src="' . $picture . '" width="230" alt="" style="display:block;border:0;width:100%;max-width:230px;height:auto;border-radius:6px;">';
				$cells = $layout === 'image-left'
					? ['<td width="46%" valign="middle" style="width:46%;padding:0 16px 0 0;">' . $imgCell . '</td>', '<td width="54%" valign="middle" style="width:54%;">' . $textCell . '</td>']
					: ['<td width="54%" valign="middle" style="width:54%;padding:0 16px 0 0;">' . $textCell . '</td>', '<td width="46%" valign="middle" style="width:46%;">' . $imgCell . '</td>'];
				$inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>' . implode('', $cells) . '</tr></table>';
			} else {
				$inner = self::heading($s, $values, $svc, $addImage, $c, 'center');
				if ($picture !== null && $layout !== 'steps') {
					$inner .= self::picture($picture);
				}
				if ($items !== []) {
					$inner .= $layout === 'steps'
						? self::steps($items, $values, $svc, $c)
						: self::tilesGrid($items, (int)($s['columns'] ?? 2), $values, $svc, $addImage, $c);
				}
				if ($picture !== null && $layout === 'steps') {
					$inner .= self::picture($picture);
				}
			}

			foreach ($items as $n => $item) {
				$t = trim($svc->toPlain($item['title'] ?? '', $values) . ': ' . $svc->toPlain($item['text'] ?? '', $values), ': ');
				$plain .= ($layout === 'steps' ? ($n + 1) . '. ' : '- ') . $t . PHP_EOL;
			}

			if (trim(strip_tags($inner, '<img>')) === '') {
				continue;
			}
			$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
				. '<tr><td style="padding:0 20px 16px;">'
				. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
				. '<tr><td style="background:' . $bg . ';background-color:' . $bg . ';padding:32px 24px;border-radius:8px;text-align:center;">'
				. $inner
				. '</td></tr></table></td></tr></table>';
			$plain .= PHP_EOL;
		}

		return [$html, $plain];
	}

	private static function heading(array $s, array $values, EmailCustomizationService $svc, callable $addImage, array $c, string $align): string {
		$out = '';
		if (($s['icon'] ?? '') !== '' && ($src = $addImage($s['icon'])) !== null) {
			$margin = $align === 'center' ? '0 auto 16px' : '0 0 14px';
			$out .= '<img src="' . $src . '" width="48" height="48" alt="" style="display:block;margin:' . $margin . ';border:0;width:48px;height:48px;">';
		}
		if (($s['title'] ?? '') !== '') {
			$size = $align === 'center' ? '28px' : '20px';
			$out .= '<h2 style="margin:0 0 12px;font-family:' . self::FONT . ';font-size:' . $size . ';line-height:1.25;font-weight:700;color:' . $c['title'] . ';text-align:' . $align . ';">'
				. $svc->toHtml($s['title'], $values) . '</h2>';
		}
		if (($s['text'] ?? '') !== '') {
			$out .= '<p style="margin:0 0 16px;font-family:' . self::FONT . ';font-size:16px;line-height:1.55;color:' . $c['text'] . ';text-align:' . $align . ';">'
				. $svc->toHtml($s['text'], $values) . '</p>';
		}
		return $out;
	}

	private static function picture(string $src): string {
		return '<img src="' . $src . '" width="500" alt="" style="display:block;margin:8px auto 16px;border:0;width:100%;max-width:500px;height:auto;border-radius:6px;">';
	}

	private static function tilesGrid(array $items, int $columns, array $values, EmailCustomizationService $svc, callable $addImage, array $c): string {
		$columns = max(2, min(4, $columns));
		$width = (int)floor(100 / $columns);
		$small = $columns > 2;
		$out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:8px;">';
		foreach (array_chunk($items, $columns) as $row) {
			$out .= '<tr>';
			foreach ($row as $item) {
				$cell = '';
				if (($item['icon'] ?? '') !== '' && ($src = $addImage($item['icon'])) !== null) {
					$px = $small ? 44 : 40;
					$cell .= '<img src="' . $src . '" width="' . $px . '" height="' . $px . '" alt="" style="display:block;margin:0 auto 8px;border:0;width:' . $px . 'px;height:' . $px . 'px;">';
				}
				if (($item['title'] ?? '') !== '') {
					$cell .= '<p style="margin:0 0 6px;font-family:' . self::FONT . ';font-size:' . ($small ? '14px' : '16px') . ';font-weight:700;color:' . $c['title'] . ';text-align:center;">'
						. $svc->toHtml($item['title'], $values) . '</p>';
				}
				if (($item['text'] ?? '') !== '') {
					$cell .= '<p style="margin:0;font-family:' . self::FONT . ';font-size:' . ($small ? '13px' : '14px') . ';line-height:1.5;color:' . $c['text'] . ';text-align:center;">'
						. $svc->toHtml($item['text'], $values) . '</p>';
				}
				$out .= '<td width="' . $width . '%" valign="top" style="padding:12px ' . ($small ? '4' : '10') . 'px;width:' . $width . '%;">' . $cell . '</td>';
			}
			for ($i = count($row); $i < $columns; $i++) {
				$out .= '<td width="' . $width . '%" style="width:' . $width . '%;"></td>';
			}
			$out .= '</tr>';
		}
		return $out . '</table>';
	}

	/** Tiles beside a picture: a simple left-aligned list. */
	private static function tilesList(array $items, array $values, EmailCustomizationService $svc, callable $addImage, array $c): string {
		$out = '';
		foreach ($items as $item) {
			$out .= '<p style="margin:0 0 10px;font-family:' . self::FONT . ';font-size:14px;line-height:1.5;color:' . $c['text'] . ';text-align:left;">'
				. (($item['title'] ?? '') !== '' ? '<strong style="color:' . $c['title'] . ';">' . $svc->toHtml($item['title'], $values) . '</strong><br>' : '')
				. $svc->toHtml($item['text'] ?? '', $values) . '</p>';
		}
		return $out;
	}

	private static function steps(array $items, array $values, EmailCustomizationService $svc, array $c): string {
		$out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:8px;">';
		foreach ($items as $n => $item) {
			$badge = '<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>'
				. '<td width="36" height="36" align="center" valign="middle" style="width:36px;height:36px;background:' . $c['accent'] . ';background-color:' . $c['accent'] . ';border-radius:18px;font-family:' . self::FONT . ';font-size:16px;font-weight:700;color:' . $c['badgeText'] . ';text-align:center;">'
				. ($n + 1) . '</td></tr></table>';
			$text = (($item['title'] ?? '') !== '' ? '<strong style="color:' . $c['title'] . ';">' . $svc->toHtml($item['title'], $values) . '</strong><br>' : '')
				. $svc->toHtml($item['text'] ?? '', $values);
			$out .= '<tr><td width="52" valign="top" style="width:52px;padding:10px 0;">' . $badge . '</td>'
				. '<td valign="middle" style="padding:10px 0;font-family:' . self::FONT . ';font-size:15px;line-height:1.5;color:' . $c['text'] . ';text-align:left;">' . $text . '</td></tr>';
		}
		return $out . '</table>';
	}

	public static function isDark(string $hex): bool {
		$hex = ltrim($hex, '#');
		if (strlen($hex) !== 6) {
			return false;
		}
		[$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
		return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255 < 0.55;
	}
}
