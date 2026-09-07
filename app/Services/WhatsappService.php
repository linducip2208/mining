<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Single place to build WhatsApp conversion URLs.
 * Never concatenate wa.me links manually in Blade.
 */
final class WhatsappService
{
    public const DEFAULT_NUMBER = '6281296052010';

    public const PRICE_AMOUNT = 12000000;

    public static function number(): string
    {
        $raw = (string) Setting::get('marketing.whatsapp_number', self::DEFAULT_NUMBER);

        return self::normalize($raw);
    }

    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (str_starts_with($digits, '08')) {
            $digits = '62'.substr($digits, 1);
        }
        if (str_starts_with($digits, '8') && ! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits !== '' ? $digits : self::DEFAULT_NUMBER;
    }

    public static function priceFormatted(): string
    {
        return 'Rp'.number_format(self::PRICE_AMOUNT, 0, ',', '.');
    }

    public static function priceShort(): string
    {
        return 'Rp12 Juta';
    }

    /**
     * @param  array{source?:string, page?:string, cluster?:string, intent?:string}  $attribution
     */
    public static function link(string $message, array $attribution = []): string
    {
        $params = array_filter([
            'source' => $attribution['source'] ?? 'pseo',
            'page' => $attribution['page'] ?? null,
            'cluster' => $attribution['cluster'] ?? null,
            'intent' => $attribution['intent'] ?? null,
        ]);
        $text = $message;
        if ($params !== []) {
            $text .= "\n\n--\n".http_build_query($params, '', ' | ');
        }

        return 'https://wa.me/'.self::number().'?text='.rawurlencode($text);
    }

    public static function defaultMessage(string $pageTitle, string $intent): string
    {
        $template = (string) Setting::get(
            'marketing.whatsapp_default_message',
            "Halo, saya tertarik dengan Source Code ERP Mining mulai Rp12 juta.\n\nSaya melihat halaman:\n{PAGE_TITLE}\n\nSaya ingin informasi mengenai:\n{INTENT}\n\nURL:\n{CURRENT_URL}"
        );

        return strtr($template, [
            '{PAGE_TITLE}' => $pageTitle,
            '{INTENT}' => $intent,
            '{CURRENT_URL}' => url()->current(),
        ]);
    }
}
