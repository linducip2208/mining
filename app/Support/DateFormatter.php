<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class DateFormatter
{
    public static function long(mixed $value): string
    {
        if (! $value) {
            return '—';
        }
        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $date->locale('id')->translatedFormat('d F Y');
    }

    public static function short(mixed $value): string
    {
        if (! $value) {
            return '—';
        }
        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $date->format('d/m/Y');
    }
}
