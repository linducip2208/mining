<?php

namespace App\Services\Bfj;

use Carbon\Carbon;

/**
 * Indonesian locale parsers (§66-§68). Money parser ≠ quantity parser.
 * Every parse keeps source_value for audit trail.
 */
final class BfjParsers
{
    /** @return array{value:float|null,error:string|null} */
    public static function parseMoney(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['value' => null, 'error' => 'MISSING_FIELD'];
        }
        if (is_numeric($raw)) {
            return ['value' => (float) $raw, 'error' => null];
        }
        $s = trim((string) $raw);
        $s = preg_replace('/^(Rp\.?|IDR)\s*/i', '', $s) ?? '';
        // 135.975.000 → 135975000 ; 388,500,000 → 388500000 ; 13,91 unlikely money but keep comma-decimal
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
            $s = str_replace(',', '', $s);
        } elseif (str_contains($s, ',') && ! str_contains($s, '.')) {
            // could be thousand separator Rp388,500,000
            $parts = explode(',', $s);
            $s = end($parts) !== false && strlen((string) end($parts)) === 2
                ? str_replace(',', '', $s) === $s ? $s : preg_replace('/,(\d{2})$/', '.$1', str_replace(',', '', $s)) ?? $s
                : str_replace(',', '', $s);
        }
        $s = preg_replace('/[^0-9.\-]/', '', $s) ?? '';
        if ($s === '' || ! is_numeric($s)) {
            return ['value' => null, 'error' => 'NUMBER_PATTERN_VARIANCE'];
        }

        return ['value' => (float) $s, 'error' => null];
    }

    /** @return array{value:float|null,error:string|null} */
    public static function parseQty(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['value' => null, 'error' => 'ERROR_MISSING_QTY'];
        }
        if (is_numeric($raw)) {
            return ['value' => (float) $raw, 'error' => null];
        }
        $s = trim((string) $raw);
        // Indonesian decimal comma: 4,51 → 4.51 ; 13.91 stays
        if (preg_match('/^-?\d+,\d+$/', $s)) {
            $s = str_replace(',', '.', $s);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        $s = preg_replace('/[^0-9.\-]/', '', $s) ?? '';
        if ($s === '' || ! is_numeric($s)) {
            return ['value' => null, 'error' => 'NUMBER_PATTERN_VARIANCE'];
        }

        return ['value' => (float) $s, 'error' => null];
    }

    /** @return array{value:string|null,error:string|null,ambiguous:bool} */
    public static function parseDate(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['value' => null, 'error' => 'MISSING_FIELD', 'ambiguous' => false];
        }
        if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            try {
                $d = Carbon::create(1899, 12, 30)->addDays((int) $raw);
                if ($d->year < 1990 || $d->year > 2100) {
                    return ['value' => null, 'error' => 'INVALID_DATE', 'ambiguous' => false];
                }

                return ['value' => $d->format('Y-m-d'), 'error' => null, 'ambiguous' => false];
            } catch (\Throwable) {
                return ['value' => null, 'error' => 'INVALID_DATE', 'ambiguous' => false];
            }
        }
        $s = trim((string) $raw);
        if (in_array(strtoupper($s), ['#ERROR!', '#VALUE!', '#N/A', 'ERROR'], true)) {
            return ['value' => null, 'error' => 'FORMULA_ERROR', 'ambiguous' => false];
        }
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd M Y', 'd F Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $s);
                $errors = Carbon::getLastErrors();
                if ($d && empty($errors['warning_count']) && empty($errors['error_count']) && $d->format($fmt) === $s) {
                    if ($d->year < 1990 || $d->year > 2100) {
                        return ['value' => null, 'error' => 'INVALID_DATE', 'ambiguous' => false];
                    }
                    $ambiguous = false;
                    if (str_contains($s, '/')) {
                        $p = explode('/', $s);
                        $ambiguous = count($p) === 3 && (int) $p[0] <= 12 && (int) $p[1] <= 12 && $p[0] !== $p[1];
                    }

                    return ['value' => $d->format('Y-m-d'), 'error' => null, 'ambiguous' => $ambiguous];
                }
            } catch (\Throwable) {
                continue;
            }
        }
        try {
            $d = Carbon::parse($s);
            if ($d->year < 1990 || $d->year > 2100) {
                return ['value' => null, 'error' => 'INVALID_DATE', 'ambiguous' => false];
            }

            return ['value' => $d->format('Y-m-d'), 'error' => null, 'ambiguous' => false];
        } catch (\Throwable) {
            return ['value' => null, 'error' => 'INVALID_DATE', 'ambiguous' => false];
        }
    }

    /** "08:00", "8.5", "8:30" → decimal hours. */
    public static function parseHours(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (float) $raw;
        }
        $s = trim((string) $raw);
        if (preg_match('/^(\d{1,2})[:.](\d{1,2})$/', $s, $m)) {
            return (int) $m[1] + ((int) $m[2] / 60);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    public static function timeToMinutes(?string $t): ?int
    {
        if (! $t) {
            return null;
        }
        $t = trim(str_replace('.', ':', $t));
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $t, $m)) {
            return ((int) $m[1] * 60) + (int) $m[2];
        }

        return null;
    }
}
