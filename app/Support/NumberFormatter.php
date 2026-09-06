<?php

namespace App\Support;

use App\Models\Setting;

final class NumberFormatter
{
    public static function decimal(mixed $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, $decimals, ',', '.');
    }

    public static function money(mixed $value, ?string $currency = null): string
    {
        $currency ??= (string) Setting::get('finance.default_currency', Setting::get('general.default_currency', 'IDR'));
        $symbol = ['IDR' => 'Rp', 'USD' => '$'][$currency] ?? $currency;

        return $symbol.' '.self::decimal($value, (int) Setting::get('general.decimal_places', 2));
    }
}
