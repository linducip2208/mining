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
        // scientific notation from Excel XML (4.85082564E8) — the only letter-bearing form allowed
        if (preg_match('/^-?\d+(\.\d+)?[Ee][+-]?\d+$/', $s)) {
            return ['value' => (float) $s, 'error' => null];
        }
        // free text with letters is NEVER money ("SISA ... JULI 2026" must not become 2026)
        if (preg_match('/[a-zA-Z]/', preg_replace('/^(Rp\.?|IDR)\s*/i', '', $s) ?? '')) {
            return ['value' => null, 'error' => 'NUMBER_PATTERN_VARIANCE'];
        }
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
        // quantities never contain letters ("SPLIT 1/2" must not become 12)
        if (preg_match('/[a-zA-Z]/', $s)) {
            return ['value' => null, 'error' => 'NUMBER_PATTERN_VARIANCE'];
        }
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

    /** Indonesian + English month names with typo tolerance (AGUSTSU → AGUSTUS). */
    public const MONTHS_ID = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4, 'MEI' => 5, 'JUNI' => 6,
        'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
        'JANUARY' => 1, 'FEBRUARY' => 2, 'MARCH' => 3, 'MAY' => 5, 'JUNE' => 6, 'JULY' => 7,
        'AUGUST' => 8, 'OCTOBER' => 10, 'DECEMBER' => 12,
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'JUN' => 6, 'JUL' => 7, 'AGU' => 8, 'AGST' => 8,
        'SEP' => 9, 'OKT' => 10, 'OCT' => 10, 'NOV' => 11, 'DES' => 12, 'DEC' => 12,
    ];

    public static function monthNumber(string $name): ?int
    {
        $u = mb_strtoupper(trim($name));
        if (isset(self::MONTHS_ID[$u])) {
            return self::MONTHS_ID[$u];
        }
        $best = null;
        $bestDist = 3;
        foreach (self::MONTHS_ID as $key => $num) {
            if (strlen($key) < 4) {
                continue;
            }
            $d = levenshtein($u, $key);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $num;
            }
        }

        return $bestDist <= 2 ? $best : null;
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
        // normalize single-digit parts so strict round-trip checks pass: 19/8/2026 → 19/08/2026
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $s, $dm)) {
            $s = sprintf('%02d/%02d/%04d', $dm[1], $dm[2], $dm[3]);
        }
        // Indonesian text dates first: "3 Agustus 2026", "30 Agustsu 2026" (typo-tolerant)
        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/', $s, $m) && ($mo = self::monthNumber($m[2])) !== null) {
            if (! checkdate($mo, (int) $m[1], (int) $m[3])) {
                return ['value' => null, 'error' => 'INVALID_DATE', 'ambiguous' => false];
            }
            if ((int) $m[3] < 1990 || (int) $m[3] > 2100) {
                return ['value' => null, 'error' => 'INVALID_DATE', 'ambiguous' => false];
            }

            return ['value' => sprintf('%04d-%02d-%02d', $m[3], $mo, $m[1]), 'error' => null, 'ambiguous' => false];
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
        if ($t === null || trim($t) === '') {
            return null;
        }
        $t = trim(str_replace(',', '.', $t));
        // decimal hours as stored by BFJ timesheets: 8.0 → 08:00, 11.3 → 11:18, 21.5 → 21:30
        if (is_numeric($t)) {
            return (int) round(((float) $t) * 60);
        }
        $t = str_replace('.', ':', $t);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $t, $m)) {
            return ((int) $m[1] * 60) + (int) $m[2];
        }

        return null;
    }

    /**
     * Period plausibility: "PERIODE AGUSTUS 2026" / "AGUSTUS 2026" / "Tanggal 1 September 2026".
     *
     * @return array{0:int,1:int}|null [year, month]
     */
    public static function parsePeriodLabel(?string $label): ?array
    {
        if (! $label) {
            return null;
        }
        $u = mb_strtoupper($label);
        if (preg_match('/([A-Z]+)\s+(\d{4})/', $u, $m) && ($mo = self::monthNumber($m[1])) !== null) {
            return [(int) $m[2], $mo];
        }

        return null;
    }

    /** Warn when a parsed date falls outside the sheet period ±1 month (e.g. 30/07/2027 typos). */
    public static function periodMismatch(?string $ymd, ?string $periodLabel): bool
    {
        if (! $ymd || ! ($p = self::parsePeriodLabel($periodLabel))) {
            return false;
        }
        try {
            $d = Carbon::parse($ymd);
            $diff = abs(($d->year - $p[0]) * 12 + ($d->month - $p[1]));

            return $diff > 1;
        } catch (\Throwable) {
            return false;
        }
    }
}
