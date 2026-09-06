<?php

namespace App\Support;

use App\Models\Setting;

final class CurrencyFormatter
{
    public static function code(): string
    {
        return strtoupper((string) Setting::get('finance.default_currency', Setting::get('general.default_currency', 'IDR')));
    }

    public static function symbol(): string
    {
        return self::code() === 'USD' ? '$' : 'Rp';
    }

    public static function format(float|int|string|null $amount, ?int $decimals = null): string
    {
        $decimals ??= max(0, min(4, (int) Setting::get('general.decimal_places', self::code() === 'IDR' ? 0 : 2)));
        $number = number_format((float) ($amount ?? 0), $decimals, ',', '.');

        return Setting::get('general.currency_position', 'before') === 'after'
            ? $number.' '.self::symbol()
            : self::symbol().' '.$number;
    }
}
