<?php

namespace App\Services\Bfj;

/**
 * Real finance workbook parsers (§18-§23).
 *
 * - FINANCE_DETAIL (Laporan Rekap, ARUS KAS detail area): serial/text date,
 *   Tipe (Dana Masuk / Dana Keluar), category, nota, description, qty,
 *   unit price, total. Personal transfers (Rek Fredy Jhon, Rek Alasen,
 *   Pinjaman Karyawan) are flagged, never auto-posted.
 * - FINANCE_STATEMENT (Laporan ALL Pak Lasen, ALL, Sheet1, ARUS KAS header
 *   block): label/value pairs → benchmark entries with section tracking
 *   (Ditambah/Dikurangi). Opening + inflow − outflow = closing is verified
 *   by the reconciler, never asserted here.
 */
final class BfjRealFinanceParser
{
    public static function detail(array $layout, array $grid, ?string $periodLabel = null): array
    {
        $rows = [];
        [$c1, $c2] = $layout['span'];
        $cols = BfjRealCommon::findCols($layout['headers'], 'TANGGAL', 'TIPE', 'KODE', 'NOTA', 'DESKRIPSI', 'PEMASUKAN', 'PENGELUARAN', 'QTY', 'HARGA', 'TOTAL', 'KET');
        $cDate = $cols['TANGGAL'] ?? $c1;
        $cTipe = $cols['TIPE'] ?? $c1 + 1;
        $cKat = $cols['KODE'] ?? $c1 + 2;
        $cNota = $cols['NOTA'] ?? $c1 + 3;
        $cDesc = $cols['DESKRIPSI'] ?? $cols['PEMASUKAN'] ?? $c1 + 4;
        $cQty = $cols['QTY'] ?? $c1 + 5;
        $cHarga = $cols['HARGA'] ?? $c1 + 6;
        $cTotal = $cols['TOTAL'] ?? $c1 + 7;
        $lastDate = null;
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            if ($layout['stop_row'] !== null && $rn >= $layout['stop_row']) {
                break;
            }
            $row = $grid[$rn];
            // side summary tables share rows — only the detail span is read
            $desc = BfjRealCommon::v($row, $cDesc);
            $total = BfjRealCommon::money(BfjRealCommon::v($row, $cTotal));
            $dateRaw = BfjRealCommon::v($row, $cDate);
            $tipe = BfjRealCommon::v($row, $cTipe);
            // table-arithmetic rows (SUM(Table_13...), I53+I123) with no description
            // are control totals, not transactions
            if ($desc === '' && BfjRealCommon::hasFormula($row, $cTotal) && $total !== null) {
                $f = $row[$cTotal]['f'] ?? '';
                $fp = BfjFingerprinter::row(['tabletotal' => $f, 'a' => $total]);
                $rows[] = [
                    'normalized' => ['dimension' => 'TABLE_TOTAL|R'.$rn, 'label' => $f, 'amount' => $total, 'flow_type' => 'TABLE_TOTAL', 'benchmark_type' => 'TABLE_TOTAL'],
                    'issues' => [BfjRealCommon::issue('TABLE_TOTAL', 'WARNING', "R{$rn}: total tabel {$total} — benchmark, bukan transaksi")],
                    'fingerprint' => $fp, 'status' => 'WARNING', 'posting_effect' => 'NONE',
                    'source' => BfjRealCommon::source($row, $c1, $c2),
                    'row' => $rn,
                ];

                continue;
            }
            if ($desc === '' && $total === null) {
                continue;
            }
            $issues = [];
            $d = BfjRealCommon::dateCell($dateRaw);
            $date = $d['value'];
            if ($date === null && $lastDate !== null && ($desc !== '' || $total !== null)) {
                $date = $lastDate;
                $issues[] = BfjRealCommon::issue('DATE_FORWARD_FILLED', 'WARNING', "R{$rn}: tanggal mengikuti baris {$lastDate} di atasnya — verifikasi");
            }
            if ($date === null && ($desc !== '' || $total !== null)) {
                $issues[] = BfjRealCommon::issue($d['error'] ?? 'MISSING_FIELD', 'ERROR', "R{$rn}: tanggal kosong pada '{$desc}'");
            } elseif ($periodLabel && $date && BfjParsers::periodMismatch($date, $periodLabel)) {
                $issues[] = BfjRealCommon::issue('PERIOD_MISMATCH', 'WARNING', "R{$rn}: {$date} di luar {$periodLabel}");
            }
            if ($date !== null) {
                $lastDate = $date;
            }
            if ($total === null) {
                $issues[] = BfjRealCommon::issue('MISSING_FIELD', 'ERROR', "R{$rn}: total kosong pada '{$desc}'");
            }
            $isMasuk = str_contains(mb_strtoupper($tipe), 'MASUK');
            $flow = self::flowType($desc, $tipe);
            $personal = self::personalHint($desc);
            if ($personal) {
                $issues[] = BfjRealCommon::issue('PERSONAL_CLEARING_CANDIDATE', 'WARNING', "R{$rn}: kandidat personal/clearing — {$personal}");
            }
            if ($flow === 'TRANSFER_INTERNAL') {
                $issues[] = BfjRealCommon::issue('INTERNAL_TRANSFER', 'WARNING', "R{$rn}: transfer internal — bukan pendapatan/beban");
            }
            $normalized = [
                'date' => $date, 'description' => $desc,
                'category' => BfjRealCommon::v($row, $cKat),
                'reference' => BfjRealCommon::v($row, $cNota),
                'qty' => BfjRealCommon::qty(BfjRealCommon::v($row, $cQty)),
                'unit_price' => BfjRealCommon::money(BfjRealCommon::v($row, $cHarga)),
                'inflow' => $isMasuk ? ($total ?? 0) : 0,
                'outflow' => $isMasuk ? 0 : ($total ?? 0),
                'net' => $isMasuk ? ($total ?? 0) : -($total ?? 0),
                'flow_type' => $flow,
                'tipe_raw' => $tipe,
            ];
            $normalized['fingerprint'] = BfjFingerprinter::row(['f' => $date ?? '', 'x' => $desc, 't' => $total]);
            $rows[] = [
                'normalized' => $normalized, 'issues' => $issues,
                'fingerprint' => $normalized['fingerprint'],
                'status' => BfjRealCommon::any($issues, fn ($i) => $i['severity'] === 'ERROR') ? 'ERROR' : ($issues === [] ? 'READY' : 'WARNING'),
                'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $c1, $c2),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'IMPORT', 'notes' => ['staging' => 'finance detail, RECONCILIATION_ONLY effect']];
    }

    /**
     * Label/value statement sheets. Label column + amount column are guessed:
     * the amount column is the one holding the most numerics in label rows.
     */
    public static function statement(array $grid, string $sheetName): array
    {
        $rows = [];
        // candidate label cols: B/C, amount cols: H..M — detect by numeric density
        $density = [];
        foreach ($grid as $rn => $row) {
            foreach ($row as $col => $cell) {
                if (is_numeric(trim($cell['v'] ?? '')) && trim($cell['v']) !== '') {
                    $density[$col] = ($density[$col] ?? 0) + 1;
                }
            }
        }
        arsort($density);
        $cAmt = array_key_first($density) ?? 7;
        $cLabel = $cAmt >= 5 ? 2 : 1;
        $section = '';
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            $row = $grid[$rn];
            $label = trim((string) ($row[$cLabel]['v'] ?? ''));
            if ($label === '' && isset($row[$cLabel - 1])) {
                $label = trim((string) ($row[$cLabel - 1]['v'] ?? ''));
            }
            $u = mb_strtoupper($label);
            if (str_contains($u, 'DITAMBAH')) {
                $section = 'INFLOW';

                continue;
            }
            if (str_contains($u, 'DIKURANGI')) {
                $section = 'OUTFLOW';

                continue;
            }
            $amt = BfjRealCommon::money(BfjRealCommon::v($row, $cAmt));
            if ($label === '' || $amt === null) {
                continue;
            }
            if (mb_strlen($label) < 3) {
                continue;
            }
            $flow = self::flowType($label, '');
            $fp = BfjFingerprinter::row(['stmt' => $sheetName, 'l' => $label, 'a' => $amt]);
            $rows[] = [
                'normalized' => [
                    'dimension' => 'STATEMENT|'.$label, 'label' => $label, 'amount' => $amt,
                    'section' => $section, 'flow_type' => $flow, 'date' => null,
                    'inflow' => $section === 'INFLOW' ? $amt : 0, 'outflow' => $section === 'OUTFLOW' ? $amt : 0,
                ],
                'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                'source' => BfjRealCommon::source($row, 0, max($cAmt, $cLabel)),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'statement lines']];
    }

    /**
     * Side summary table (Laporan Rekap cols M:O — NO | KODE PENGELUARAN | TOTAL).
     * Generic: second header row below the main header with its own span.
     */
    public static function summaryTable(array $grid, int $afterRow): array
    {
        $rows = [];
        // find second header: row with >=2 of NO/KODE/TOTAL/PENGELUARAN
        $startRow = null;
        $cols = [];
        foreach ($grid as $rn => $row) {
            if ($rn <= $afterRow) {
                continue;
            }
            $line = mb_strtoupper(implode('|', array_map(fn ($c) => trim($c['v'] ?? ''), $row)));
            $hits = (str_contains($line, 'NO') ? 1 : 0) + (str_contains($line, 'KODE') ? 1 : 0)
                + (str_contains($line, 'TOTAL') ? 1 : 0) + (str_contains($line, 'PENGELUARAN') ? 1 : 0);
            if ($hits >= 2) {
                $startRow = $rn;
                foreach ($row as $col => $cell) {
                    $cols[mb_strtoupper(trim($cell['v'] ?? ''))] = $col;
                }
                break;
            }
        }
        if ($startRow === null) {
            return ['rows' => [], 'action' => 'RECONCILE_ONLY', 'notes' => []];
        }
        $cCat = $cols['KODE PENGELUARAN'] ?? $cols['KODE'] ?? null;
        $cAmt = $cols['TOTAL PENGELUARAN (RP)'] ?? $cols['TOTAL'] ?? null;
        if ($cCat === null || $cAmt === null) {
            return ['rows' => [], 'action' => 'RECONCILE_ONLY', 'notes' => []];
        }
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $startRow) {
                continue;
            }
            $row = $grid[$rn];
            $cat = trim((string) ($row[$cCat]['v'] ?? ''));
            $amt = BfjRealCommon::money(BfjRealCommon::v($row, $cAmt));
            if ($cat === '' || $amt === null) {
                continue;
            }
            $fp = BfjFingerprinter::row(['catsum' => $cat, 'a' => $amt]);
            $rows[] = [
                'normalized' => ['dimension' => 'CATEGORY|'.$cat, 'label' => $cat, 'amount' => $amt, 'section' => 'OUTFLOW', 'flow_type' => 'OPERATING'],
                'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                'source' => BfjRealCommon::source($row, $cCat, $cAmt),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'category totals']];
    }

    /**
     * Account header block above the detail table (ARUS KAS R9-R17): opening,
     * inflow, outflow and closing cells with walk-up labels.
     * Rows: value cells in B..I, label = same-col text up to 2 rows above,
     * else row text.
     */
    public static function accountBlock(array $grid, int $headerRow, string $sheetName): array
    {
        $rows = [];
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn >= $headerRow) {
                continue;
            }
            $row = $grid[$rn];
            for ($c = 1; $c <= 8; $c++) {
                $cell = $row[$c] ?? null;
                if ($cell === null) {
                    continue;
                }
                $amt = BfjRealCommon::money(trim($cell['v'] ?? ''));
                if ($amt === null) {
                    continue;
                }
                // skip serial-looking small ints that are actually dates? keep all, label decides
                $label = '';
                for ($lr = $rn; $lr >= max(1, $rn - 2) && $label === ''; $lr--) {
                    $label = trim((string) ($grid[$lr][$c]['v'] ?? ''));
                    if (is_numeric($label)) {
                        $label = '';
                    }
                }
                if ($label === '') {
                    $label = trim(implode(' ', array_filter(array_map(fn ($cc) => trim($cc['v'] ?? ''), array_slice($row, 0, 9, true)))));
                }
                if ($label === '' || mb_strlen($label) < 3) {
                    continue;
                }
                $fp = BfjFingerprinter::row(['blk' => $sheetName, 'l' => $label, 'a' => $amt]);
                $rows[] = [
                    'normalized' => ['dimension' => 'BLOCK|'.$label, 'label' => $label, 'amount' => $amt, 'benchmark_type' => 'BLOCK'],
                    'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                    'source' => BfjRealCommon::source($row, 0, 8),
                    'row' => $rn,
                ];
            }
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'account block']];
    }

    public static function flowType(string $desc, string $tipe): string
    {
        $u = mb_strtoupper($desc.' '.$tipe);
        $personal = self::personalHint($desc.' '.$tipe);
        $isAlasen = str_contains($u, 'ALASEN') || str_contains($u, 'LASEN');
        // opening balances first (incl. "Sisa Kas ... Juli" personal-cash openings)
        if (str_contains($u, 'SALDO AWAL') || str_contains($u, 'SISA SALDO') && str_contains($u, 'JULI') || str_contains($u, 'SISA KAS') && str_contains($u, 'JULI') || str_contains($u, 'BAWAAN') || str_contains($u, 'CARRY')) {
            return 'OPENING_BALANCE';
        }
        // transfers to named persons (Fredy Jhon) are clearing/drawings, not internal
        if ($personal && ! $isAlasen) {
            return 'CLEARING';
        }
        // company ↔ tracked clearing account moves are internal transfers
        if (str_contains($u, 'TRANSFER') && (str_contains($u, 'REKENING') || str_contains($u, 'ANTAR') || str_contains($u, 'INTERNAL') || str_contains($u, 'KE REK') || str_contains($u, 'DARI REK'))) {
            return 'TRANSFER_INTERNAL';
        }
        if ($personal) {
            return 'CLEARING';
        }
        if (str_contains($u, 'MODAL') || str_contains($u, 'ANGSURAN') || str_contains($u, 'PINJAMAN') || str_contains($u, 'LOAN') || str_contains($u, 'HUTANG')) {
            return 'FINANCING';
        }
        if (str_contains($u, 'ALAT') && str_contains($u, 'BELI') || str_contains($u, 'ASET') || str_contains($u, 'INVESTASI') || str_contains($u, 'CONVEYOR')) {
            return 'INVESTING';
        }

        return 'OPERATING';
    }

    public static function personalHint(string $desc): ?string
    {
        $u = mb_strtoupper($desc);
        foreach (['REK FREDY', 'FREDY JHON', 'REKENING ALASEN', 'REK ALASEN', 'PAK LASEN', 'BAPAK ALASEN', 'PINJAMAN KARYAWAN', 'KASBON'] as $hint) {
            if (str_contains($u, $hint)) {
                return $hint;
            }
        }

        return null;
    }
}
