<?php

namespace App\Services\Bfj;

use App\Support\SpreadsheetReader;

/**
 * Real payroll workbook parsers (§24-§30).
 *
 * Employee sheets: R4 name ("Nama : Joni Irawan (Operator Timbangan)"),
 * grouped header R6 (merged groups) + sub header R7 (Mulai/Selesai/Jml),
 * daily rows R8+ with serial/text dates, decimal-hour times (8.0/16.5),
 * normal formula end−start−1 (1h break), overtime groups per employee,
 * STAND BY, BBM Jirigen (Ltr), OLI, UNIT, KEGIATAN, RITASE.
 * Recap block R39+: totals row (C39 hari kerja, E39 normal, OT group sums)
 * + pay lines (Gaji Pokok, lembur × rate, Uang Makan, Overday, Jumlah Upah,
 * Potongan, Kasbon). REKAP/Copy reference these cells with cached values.
 */
final class BfjRealPayrollParser
{
    /**
     * @param  array{header_row:int,group_row:int|null,span:array{0:int,1:int},headers:array<int,string>,groups:array<int,string>,title:array<string,string|null>}  $layout
     */
    public static function employeeDays(array $layout, array $grid, string $employee, ?string $role, ?string $periodLabel): array
    {
        $rows = [];
        [$c1, $c2] = $layout['span'];
        // column roles from groups + sub headers
        $map = self::columnRoles($layout, $grid);
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            $row = $grid[$rn];
            $dateRaw = BfjRealCommon::v($row, $map['date']);
            if ($dateRaw === '') {
                // recap block begins (R39 totals have no date in col B... except they DO: no. R39 B empty)
                if ($rn >= 39) {
                    break;
                }

                continue;
            }
            $d = BfjRealCommon::dateCell($dateRaw);
            if ($d['value'] === null) {
                continue; // sub-header echo or blank
            }
            // skip rows with no time content at all (weekends untouched: all blank)
            if (! self::hasContent($row, $map)) {
                continue;
            }
            $issues = [];
            if ($periodLabel && BfjParsers::periodMismatch($d['value'], $periodLabel)) {
                $issues[] = BfjRealCommon::issue('PERIOD_MISMATCH', 'ERROR', "R{$rn}: {$d['value']} di luar {$periodLabel} (contoh typo tahun 2027/2028)");
            }
            $start = BfjParsers::timeToMinutes(BfjRealCommon::v($row, $map['normal_start']));
            $end = BfjParsers::timeToMinutes(BfjRealCommon::v($row, $map['normal_end']));
            $recorded = BfjParsers::parseHours(BfjRealCommon::v($row, $map['normal_hours']));
            $erp = null;
            if ($start !== null && $end !== null) {
                $endAdj = $end < $start ? $end + 1440 : $end;
                $erp = max(0, ($endAdj - $start - 60) / 60);
                if ($recorded !== null && abs($erp - $recorded) > 0.02) {
                    $issues[] = BfjRealCommon::issue('HOURS_VARIANCE', 'WARNING', "R{$rn}: normal ERP {$erp} vs legacy {$recorded}");
                }
            } elseif ($recorded === null && $start === null) {
                $issues[] = BfjRealCommon::issue('MISSING_FIELD', 'WARNING', "R{$rn}: jam normal kosong (izin/absen?)");
            }
            $ot = [];
            foreach ($map['ot_groups'] as $type => $g) {
                $s = BfjParsers::timeToMinutes(BfjRealCommon::v($row, $g['start']));
                $e = BfjParsers::timeToMinutes(BfjRealCommon::v($row, $g['end']));
                $rec = BfjParsers::parseHours(BfjRealCommon::v($row, $g['hours']));
                $erpOt = null;
                if ($s !== null && $e !== null) {
                    $eAdj = $e < $s ? $e + 1440 : $e;
                    $erpOt = max(0, ($eAdj - $s) / 60);
                    if ($rec !== null && abs($erpOt - $rec) > 0.02) {
                        $issues[] = BfjRealCommon::issue('HOURS_VARIANCE', 'WARNING', "R{$rn}: {$type} ERP {$erpOt} vs legacy {$rec}");
                    }
                }
                if ($s !== null || $e !== null || ($rec !== null && $rec > 0)) {
                    $ot[$type] = ['start' => $s, 'end' => $e, 'recorded' => $rec, 'erp' => $erpOt];
                }
            }
            $normalized = [
                'employee' => $employee, 'role' => $role, 'date' => $d['value'],
                'normal_start' => $start, 'normal_end' => $end,
                'normal_recorded' => $recorded, 'normal_erp' => $erp,
                'overtime' => $ot,
                'holiday_ot' => BfjRealCommon::v($row, $map['holiday']),
                'stand' => BfjRealCommon::v($row, $map['stand']),
                'bbm' => BfjRealCommon::v($row, $map['bbm']),
                'unit' => BfjRealCommon::v($row, $map['unit']),
                'activity' => BfjRealCommon::v($row, $map['activity']),
            ];
            $normalized['fingerprint'] = BfjFingerprinter::payroll([
                'emp' => $employee, 'date' => $d['value'], 'hours' => $recorded, 'comp' => 'DAY',
            ]);
            $rows[] = [
                'normalized' => $normalized, 'issues' => $issues,
                'fingerprint' => $normalized['fingerprint'],
                'status' => BfjRealCommon::any($issues, fn ($i) => $i['severity'] === 'ERROR') ? 'ERROR' : ($issues === [] ? 'READY' : 'WARNING'),
                'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $c1, $c2),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'IMPORT', 'notes' => ['employee' => $employee, 'role' => $role]];
    }

    /**
     * Recap block R39+: totals row + labeled pay lines → benchmark entries.
     *
     * @return array{rows:array,action:string,notes:array}
     */
    public static function recapBlock(array $layout, array $grid, string $employee): array
    {
        $rows = [];
        $map = self::columnRoles($layout, $grid);
        $groups = $layout['groups'] ?? [];
        // totals row: first row >= 39 with COUNT/SUM formulas
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn < 39 || $rn > 80) {
                continue;
            }
            $row = $grid[$rn] ?? [];
            $label = BfjRealCommon::v($row, 1);
            if ($rn === 39 || ($label === '' && self::rowHasFormulas($row))) {
                // totals row: map formula targets
                foreach ($row as $col => $cell) {
                    $f = $cell['f'] ?? null;
                    $v = BfjRealCommon::money($cell['v']);
                    if ($f === null || $v === null) {
                        continue;
                    }
                    $fu = mb_strtoupper($f);
                    if (str_starts_with($fu, 'COUNT(')) {
                        $comp = 'TOTAL_HARI_KERJA';
                    } elseif (preg_match('/SUM\(\w*8:\w*3[0-9]\)/', $fu) && $col === $map['normal_hours']) {
                        $comp = 'TOTAL_NORMAL_HOURS';
                    } elseif (str_starts_with($fu, 'SUM(')) {
                        $gtype = BfjNormalizer::overtimeType($groups[$col] ?? '');
                        $comp = $gtype ? 'TOTAL_'.$gtype : 'TOTAL_RAW_'.(SpreadsheetReader::columnName($col));
                    } else {
                        continue;
                    }
                    $rows[] = self::bench($employee, $comp, $v, $row, $layout, "R{$rn} {$f}", $rn);
                }

                continue;
            }
            if ($label === '') {
                continue;
            }
            // pay lines: amount in the furthest-right numeric cell of the row
            $amt = null;
            foreach (array_reverse($row, true) as $cell) {
                $m = BfjRealCommon::money(trim($cell['v'] ?? ''));
                if ($m !== null && $m != 0) {
                    $amt = $m;
                    break;
                }
            }
            if ($amt === null) {
                // zero lines (BPJS empty, Kasbon empty) still recorded when label is a pay component
                if (! self::isPayLabel($label)) {
                    continue;
                }
                $amt = 0;
            }
            $rows[] = self::bench($employee, mb_strtoupper($label), $amt, $row, $layout, "R{$rn}", $rn);
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'recap block']];
    }

    /** REKAP / Copy of REKAP rows: cross-sheet cached values + masked bank data. */
    public static function rekapRow(array $assoc, string $sheetName, string $block = 'AGUSTUS 2026'): array
    {
        $issues = [];
        $get = fn (string ...$keys) => array_reduce($keys, fn ($carry, $k) => $carry ?? ($assoc[$k] ?? null));
        $name = $get('NAMA');
        if (! $name) {
            return ['rows' => [], 'action' => 'RECONCILE_ONLY', 'notes' => ['skipped' => 'no name']];
        }
        $comps = [
            'UPAH' => $get('UPAH BRUTO JULI', 'UPAH'),
            'LEMBUR_JAM_PERTAMA' => $get('LEMBUR JAM PERTAMA'),
            'LEMBUR_SORE_MALAM' => $get('LEMBUR SORE-MALAM'),
            'JUMLAH_JAM_LEMBUR' => $get('JUMLAH JAM LEMBUR'),
            'JUMLAH_HARI_KERJA' => $get('JUMLAH HARI KERJA'),
            'OVERDAY' => $get('OVERDAY (Tanggal Merah)', 'OVERDAY'),
            'UANG_MAKAN' => $get('UANG MAKAN'),
            'KASBON' => $get('KASBON'),
            'TIDAK_HADIR' => $get('TIDAK HADIR'),
            'UPAH_NETTO' => $get('UPAH NETTO'),
        ];
        $rows = [];
        foreach ($comps as $comp => $raw) {
            $val = is_numeric($raw) ? (float) $raw : BfjRealCommon::money((string) ($raw ?? ''));
            if ($val === null) {
                continue;
            }
            $fp = BfjFingerprinter::row(['rekap' => $sheetName, 'block' => $block, 'emp' => $name, 'c' => $comp, 'v' => $val]);
            $rows[] = [
                'normalized' => [
                    'dimension' => "REKAP|{$block}|{$name}|{$comp}", 'employee' => $name, 'component' => $comp,
                    'legacy_value' => $val, 'role' => $get('JABATAN'), 'bank' => $get('NAMA BANK') ? '***MASKED***' : null,
                    'notes' => $get('KETERANGAN'), 'block' => $block,
                ],
                'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                'source' => ['NAMA' => $name, $comp => (string) $raw, 'BANK' => '***MASKED***'],
            ];
        }
        $kasbon = BfjRealCommon::money((string) ($get('KASBON') ?? ''));
        if ($kasbon) {
            $issues[] = BfjRealCommon::issue('KASBON_PRESENT', 'WARNING', "{$name}: kasbon {$kasbon} → Employee Loan/Receivable, bukan beban gaji");
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['employee' => $name], 'issues' => $issues];
    }

    /** @return array<string,mixed> col roles for grouped payroll layout */
    private static function columnRoles(array $layout, array $grid): array
    {
        $hr = $layout['header_row'];
        $headers = $layout['headers'];
        $groups = $layout['groups'] ?? [];
        $roles = ['date' => 1, 'normal_start' => 2, 'normal_end' => 3, 'normal_hours' => 4, 'holiday' => null, 'stand' => null, 'bbm' => null, 'unit' => null, 'activity' => null, 'ot_groups' => []];
        // date col: TANGGAL header
        foreach ($headers as $col => $name) {
            if (mb_strtoupper($name) === 'TANGGAL') {
                $roles['date'] = $col;
            }
        }
        // walk sub-header cells (Mulai/Selesai/Jml or BY/Jirigen/(Ltr)) left→right, assigning by group
        $subs = [];
        foreach ($grid[$hr] as $col => $cell) {
            $subs[$col] = mb_strtoupper(trim($cell['v'] ?? ''));
        }
        ksort($subs);
        $pending = null; // ['group'=>..,'start'=>col]
        foreach ($subs as $col => $sub) {
            $group = $groups[$col] ?? '';
            $gu = mb_strtoupper($group);
            if (str_contains($gu, 'JAM NORMAL') || $gu === 'TANGGAL') {
                if ($sub === 'MULAI') {
                    $roles['normal_start'] = $col;
                } elseif ($sub === 'SELESAI') {
                    $roles['normal_end'] = $col;
                } elseif ($sub === 'JML') {
                    $roles['normal_hours'] = $col;
                }

                continue;
            }
            if (str_contains($gu, 'HARI')) {
                // "Hari lembur" single-column group (holiday overtime day marker)
                $roles['holiday'] ??= $col;

                continue;
            }
            if (str_contains($gu, 'STAND')) {
                $roles['stand'] ??= $col;

                continue;
            }
            if (str_contains($gu, 'BBM') || str_contains($gu, 'OLI') || $sub === 'JIRIGEN' || $sub === '(LTR)') {
                $roles['bbm'] ??= $col;

                continue;
            }
            if (str_contains($gu, 'UNIT')) {
                $roles['unit'] ??= $col;

                continue;
            }
            if (str_contains($gu, 'KEGIATAN') || str_contains($gu, 'RITASE')) {
                $roles['activity'] ??= $col;

                continue;
            }
            $type = BfjNormalizer::overtimeType($group);
            if ($type) {
                if ($sub === 'MULAI') {
                    $pending = ['type' => $type, 'start' => $col];
                } elseif ($sub === 'SELESAI' && $pending && $pending['type'] === $type) {
                    $pending['end'] = $col;
                } elseif ($sub === 'JML' && $pending && $pending['type'] === $type) {
                    $pending['hours'] = $col;
                    $roles['ot_groups'][$type] = $pending;
                    $pending = null;
                }
            }
        }

        return $roles;
    }

    /** @param  array<int, array{v:string,f:string|null}>  $row */
    private static function hasContent(array $row, array $map): bool
    {
        foreach (['normal_start', 'normal_end', 'normal_hours', 'stand', 'bbm', 'unit', 'activity', 'holiday'] as $k) {
            if ($map[$k] === null) {
                continue;
            }
            if (trim((string) ($row[$map[$k]]['v'] ?? '')) !== '') {
                return true;
            }
        }
        foreach ($map['ot_groups'] as $g) {
            foreach (['start', 'end', 'hours'] as $k) {
                if (isset($g[$k]) && trim((string) ($row[$g[$k]]['v'] ?? '')) !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param  array<int, array{v:string,f:string|null}>  $row */
    private static function rowHasFormulas(array $row): bool
    {
        foreach ($row as $cell) {
            if (($cell['f'] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    private static function isPayLabel(string $label): bool
    {
        $u = mb_strtoupper($label);
        foreach (['GAJI POKOK', 'LEMBUR', 'UANG MAKAN', 'OVERDAY', 'JUMLAH UPAH', 'POTONGAN', 'KASBON', 'BPJS', 'TIDAK HADIR', 'JUMLAH POTONGAN'] as $k) {
            if (str_contains($u, $k)) {
                return true;
            }
        }

        return false;
    }

    private static function bench(string $employee, string $comp, float $val, array $row, array $layout, string $ref, int $rn): array
    {
        return [
            'normalized' => ['dimension' => "RECAP|{$employee}|{$comp}", 'employee' => $employee, 'component' => $comp, 'legacy_value' => $val],
            'issues' => [], 'fingerprint' => BfjFingerprinter::row(['rb' => $employee, 'c' => $comp, 'v' => $val, 'r' => $ref]),
            'status' => 'READY', 'posting_effect' => 'NONE',
            'source' => BfjRealCommon::source($row, $layout['span'][0], $layout['span'][1]),
            'row' => $rn,
        ];
    }

    /** Security attendance matrix + pay-calc blocks → benchmark entries. */
    public static function security(array $layout, array $grid): array
    {
        $rows = [];
        $rowNums = array_keys($grid);
        sort($rowNums);
        $people = []; // name => ['hk'=>float, 'colStart'=>int, 'colEnd'=>int]
        $hkCol = null;
        foreach ($layout['headers'] as $col => $name) {
            if (str_contains(mb_strtoupper($name), 'JUMLAH HK')) {
                $hkCol = $col;
            }
        }
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            $row = $grid[$rn];
            $no = BfjRealCommon::v($row, 1);
            $name = BfjRealCommon::v($row, 2);
            if (! is_numeric($no) || $name === '') {
                // pay block header? "Agung Wijaya : Security" style → person column block marker
                continue;
            }
            $hk = $hkCol !== null ? BfjRealCommon::money(BfjRealCommon::v($row, $hkCol)) : null;
            $fp = BfjFingerprinter::row(['sec' => $name, 'hk' => $hk]);
            $rows[] = [
                'normalized' => ['dimension' => "SECURITY|{$name}|HK", 'employee' => $name, 'component' => 'HADIR_DAYS', 'legacy_value' => $hk],
                'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                'source' => BfjRealCommon::source($row, 1, $hkCol ?? 5),
                'row' => $rn,
            ];
            $people[$name] = true;
        }
        // pay lines: rows with Gaji Pokok/Uang Makan/BPJS labels + amounts anywhere in row
        foreach ($rowNums as $rn) {
            $row = $grid[$rn] ?? [];
            $label = '';
            foreach ($row as $cell) {
                $v = trim($cell['v'] ?? '');
                if (preg_match('/^(Gaji Pokok|Uang Makan|Bpjs Kesehatan|Bpjs Ketenagakerjaan)/i', $v)) {
                    $label = $v;
                    break;
                }
            }
            if ($label === '') {
                continue;
            }
            $amts = [];
            foreach ($row as $cell) {
                $m = BfjRealCommon::money(trim($cell['v'] ?? ''));
                if ($m !== null && $m > 1000) {
                    $amts[] = $m;
                }
            }
            foreach (array_keys($people) as $i => $pname) {
                if (! isset($amts[$i])) {
                    continue;
                }
                $rows[] = [
                    'normalized' => ['dimension' => "SECURITY|{$pname}|".mb_strtoupper($label), 'employee' => $pname, 'component' => mb_strtoupper($label), 'legacy_value' => $amts[$i]],
                    'issues' => [], 'fingerprint' => BfjFingerprinter::row(['secpay' => $pname, 'l' => $label, 'a' => $amts[$i]]),
                    'status' => 'READY', 'posting_effect' => 'NONE',
                    'source' => BfjRealCommon::source($row, 0, 10),
                    'row' => $rn,
                ];
            }
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'security matrix']];
    }

    /** "Nama : Joni Irawan (Operator Timbangan)" → [name, role]. */
    public static function employeeFromTitle(array $grid, string $tabName): array
    {
        foreach ($grid as $rn => $row) {
            if ($rn > 8) {
                break;
            }
            foreach ($row as $cell) {
                $v = trim($cell['v'] ?? '');
                if (preg_match('/nama\s*:\s*(.+)/i', $v, $m)) {
                    $full = trim($m[1]);
                    if (preg_match('/^(.+?)\s*\((.+)\)$/', $full, $mm)) {
                        return [trim($mm[1]), trim($mm[2])];
                    }

                    return [$full, null];
                }
            }
        }

        return [$tabName, null];
    }
}
