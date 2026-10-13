<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Mail;

use OCA\GovMailBranding\Service\EmailCustomizationService;

/**
 * Turns the welcome-email sections designed in the admin tab into
 * email-client-safe HTML (tables + inline styles only, no CSS classes,
 * no external images) plus a plain-text equivalent.
 *
 * Images are embedded as inline attachments (cid:) through $addImage, so
 * Outlook/Gmail show them without "load remote images".
 */
final class WelcomeRenderer {
	/**
	 * @param callable(string $id): ?string $addImage returns the cid: src, or null if the image is missing
	 * @return array{0: string, 1: string} [html, plain]
	 */
	public static function render(array $sections, array $values, EmailCustomizationService $svc, callable $addImage): array {
		$html = '';
		$plain = '';
		$font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif";

		foreach ($sections as $s) {
			$bg = $s['bg'] ?? '#f4f6fb';
			[$titleColor, $textColor] = self::textColors($bg);

			$inner = '';
			if (($s['icon'] ?? '') !== '' && ($src = $addImage($s['icon'])) !== null) {
				$inner .= '<img src="' . $src . '" width="48" height="48" alt="" style="display:block;margin:0 auto 16px;border:0;width:48px;height:48px;">';
			}
			if (($s['title'] ?? '') !== '') {
				$inner .= '<h2 style="margin:0 0 12px;font-family:' . $font . ';font-size:26px;line-height:1.25;font-weight:700;color:' . $titleColor . ';text-align:center;">'
					. $svc->toHtml($s['title'], $values) . '</h2>';
				$plain .= mb_strtoupper($svc->toPlain($s['title'], $values)) . PHP_EOL;
			}
			if (($s['text'] ?? '') !== '') {
				$inner .= '<p style="margin:0 0 16px;font-family:' . $font . ';font-size:16px;line-height:1.5;color:' . $textColor . ';text-align:center;">'
					. $svc->toHtml($s['text'], $values) . '</p>';
				$plain .= $svc->toPlain($s['text'], $values) . PHP_EOL;
			}
			if (($s['image'] ?? '') !== '' && ($src = $addImage($s['image'])) !== null) {
				$inner .= '<img src="' . $src . '" width="500" alt="" style="display:block;margin:8px auto 16px;border:0;width:100%;max-width:500px;height:auto;">';
			}

			$items = array_values(array_filter($s['items'] ?? [], static fn ($i) => ($i['title'] ?? '') !== '' || ($i['text'] ?? '') !== ''));
			if ($items !== []) {
				$inner .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:8px;">';
				foreach (array_chunk($items, 2) as $row) {
					$inner .= '<tr>';
					foreach ($row as $item) {
						$cell = '';
						if (($item['icon'] ?? '') !== '' && ($src = $addImage($item['icon'])) !== null) {
							$cell .= '<img src="' . $src . '" width="40" height="40" alt="" style="display:block;margin:0 auto 8px;border:0;width:40px;height:40px;">';
						}
						if (($item['title'] ?? '') !== '') {
							$cell .= '<p style="margin:0 0 6px;font-family:' . $font . ';font-size:16px;font-weight:700;color:' . $titleColor . ';text-align:center;">'
								. $svc->toHtml($item['title'], $values) . '</p>';
						}
						if (($item['text'] ?? '') !== '') {
							$cell .= '<p style="margin:0;font-family:' . $font . ';font-size:14px;line-height:1.5;color:' . $textColor . ';text-align:center;">'
								. $svc->toHtml($item['text'], $values) . '</p>';
						}
						$inner .= '<td width="50%" valign="top" style="padding:12px 10px;width:50%;">' . $cell . '</td>';
						$plain .= '- ' . trim($svc->toPlain($item['title'] ?? '', $values) . ': ' . $svc->toPlain($item['text'] ?? '', $values), ': ') . PHP_EOL;
					}
					if (count($row) === 1) {
						$inner .= '<td width="50%" style="width:50%;"></td>';
					}
					$inner .= '</tr>';
				}
				$inner .= '</table>';
			}

			if ($inner === '') {
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

	/**
	 * Dark text on light backgrounds, white text on dark ones.
	 *
	 * @return array{0: string, 1: string} [title colour, body colour]
	 */
	public static function textColors(string $bg): array {
		$hex = ltrim($bg, '#');
		if (strlen($hex) !== 6) {
			return ['#1f2a44', '#4a5568'];
		}
		[$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
		$luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
		return $luminance < 0.55 ? ['#ffffff', '#e8ecf3'] : ['#1f2a44', '#4a5568'];
	}
}
