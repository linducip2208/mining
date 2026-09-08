<?php

namespace App\Services\Bfj;

use App\Support\SpreadsheetReader;

/**
 * Shared helpers for real-BFJ sheet parsers.
 *
 * Conventions:
 * - Every parser works on the rich grid: row number => [col0 => ['v','f','cached']].
 * - source keeps raw cell values + formula presence for audit (§68).
 * - Money parser != quantity parser; scientific notation (4.85082564E8) is numeric.
 * - NOTHING is auto-corrected: anomalies become issues or SKIPPED rows.
 */
final class BfjRealCommon
{
    /** @param  array<int, array{v:string,f:string|null,cached:bool}>  $row */
    public static function v(array $row, ?int $col): string
    {
        if ($col === null) {
            return '';
        }

        return trim((string) ($row[$col]['v'] ?? ''));
    }

    /** @param  array<int, array{v:string,f:string|null,cached:bool}>  $row */
    public static function hasFormula(array $row, int $col): bool
    {
        return ($row[$col]['f'] ?? null) !== null;
    }

    public static function money(?string $raw): ?float
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $r = BfjParsers::parseMoney(trim($raw));

        return $r['value'];
    }

    public static function qty(?string $raw): ?float
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $r = BfjParsers::parseQty(trim($raw));

        return $r['value'];
    }

    /** @return array{value:string|null,error:string|null} */
    public static function dateCell(?string $raw): array
    {
        $r = BfjParsers::parseDate($raw);

        return ['value' => $r['value'], 'error' => $r['error']];
    }

    public static function isBlankRow(array $row, int $c1, int $c2): bool
    {
        for ($c = $c1; $c <= $c2; $c++) {
            if (trim((string) ($row[$c]['v'] ?? '')) !== '' || ($row[$c]['f'] ?? null) !== null) {
                return false;
            }
        }

        return true;
    }

    /** "Tanggal 1 September 2026" → 2026-09-01. */
    public static function titleDay(?string $label): ?string
    {
        if (! $label) {
            return null;
        }
        if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/', $label, $m) && ($mo = BfjParsers::monthNumber($m[2])) !== null) {
            if (! checkdate($mo, (int) $m[1], (int) $m[3])) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $m[3], $mo, $m[1]);
        }

        return null;
    }

    public static function issue(string $code, string $severity, string $message): array
    {
        return ['code' => $code, 'severity' => $severity, 'message' => mb_substr($message, 0, 500)];
    }

    public static function any(array $arr, callable $cb): bool
    {
        foreach ($arr as $v) {
            if ($cb($v)) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<int,string>  $headers col => name */
    public static function findCols(array $headers, string ...$needles): array
    {
        $out = [];
        foreach ($headers as $col => $name) {
            $u = mb_strtoupper($name);
            foreach ($needles as $n) {
                if (str_contains($u, $n)) {
                    $out[$n] ??= $col;
                }
            }
        }

        return $out;
    }

    /** Source snapshot: col letter => raw value (+ [F] marker when formula without cache). */
    public static function source(array $row, int $c1, int $c2): array
    {
        $out = [];
        for ($c = $c1; $c <= $c2; $c++) {
            $cell = $row[$c] ?? ['v' => '', 'f' => null, 'cached' => false];
            $v = trim((string) ($cell['v'] ?? ''));
            if ($v === '' && ($cell['f'] ?? null) !== null) {
                $v = '[FORMULA_NO_CACHE]';
            }
            $out[SpreadsheetReader::columnName($c)] = $v;
        }

        return $out;
    }
}
