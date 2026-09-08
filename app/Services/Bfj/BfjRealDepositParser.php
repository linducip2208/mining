<?php

namespace App\Services\Bfj;

/**
 * Real DEPOSIT MATERIAL BULAN AGUSTUS 2026 parsers (§13-§17).
 *
 * Sheet kinds:
 * - SISA DEPOSIT: benchmark rows incl. cross-sheet cached balances.
 * - UANG RITEL (JONI): retail cash log → benchmark (finance-adjacent).
 * - RITEL TF + customer sheets (PAK SISWAN, ARI BANJAR, PAK RUDI, PAK RAHMAT,
 *   PANGLONG, BIMO, SMJ 2000M3): delivery/deposit rows. Unit-price ref rows
 *   (R7-R8, no date) are skipped. Deposit-only rows (no delivery) become
 *   DEPOSIT/OPENING entries with the date taken from KET when needed.
 * - SMJ: monthly recap table → benchmark.
 * - MUAT BAWAH (ANTON): loading-point log, no amounts → IGNORE (reference).
 * - STOCK PAK DIO: customer stock log → IGNORE for deposit (reference).
 * Second tables sharing the same rows (PAK RAHMAT daily hauling, SMJ monthly)
 * are excluded by the layout column span.
 */
final class BfjRealDepositParser
{
    public static function sisa(array $layout, array $grid): array
    {
        $rows = [];
        [$c1] = $layout['span'];
        $cols = BfjRealCommon::findCols($layout['headers'], 'NAMA', 'JENIS MATERIAL', 'SISA DEPOSIT');
        $cName = $cols['NAMA'] ?? $c1 + 1;
        $cMat = $cols['JENIS MATERIAL'] ?? $c1 + 2;
        $cBal = $cols['SISA DEPOSIT'] ?? $c1 + 3;
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            $row = $grid[$rn];
            $name = BfjRealCommon::v($row, $cName);
            $matCell = BfjRealCommon::v($row, $cMat);
            $balRaw = BfjRealCommon::v($row, $cBal);
            $bal = BfjRealCommon::money($balRaw);
            if ($name === '' && $bal === null) {
                continue;
            }
            $issues = [];
            if ($matCell !== '' && is_numeric($matCell)) {
                $issues[] = BfjRealCommon::issue('NUMBER_PATTERN_VARIANCE', 'WARNING', "R{$rn}: kolom JENIS MATERIAL berisi serial ({$matCell})");
            }
            if ($name === '') {
                $issues[] = BfjRealCommon::issue('MISSING_FIELD', 'WARNING', "R{$rn}: nama kosong pada SISA DEPOSIT");
            }
            if ($bal === null) {
                $issues[] = BfjRealCommon::issue('MISSING_FIELD', 'WARNING', "R{$rn}: saldo kosong untuk {$name}");
            }
            $isHutang = str_contains(mb_strtoupper($name), 'HUTANG');
            if ($isHutang) {
                $issues[] = BfjRealCommon::issue('AP_LIABILITY', 'WARNING', "R{$rn}: '{$name}' adalah hutang BFJ (AP-like), bukan deposit customer");
            }
            $fp = BfjFingerprinter::row(['sisa' => $name, 'bal' => $bal]);
            $rows[] = [
                'normalized' => [
                    'dimension' => 'SISA|'.$name, 'customer' => $name !== '' ? BfjNormalizer::normalizeCustomer($name) : null,
                    'customer_legacy' => $name, 'material_note' => BfjRealCommon::v($row, $cMat),
                    'legacy_balance' => $bal, 'is_hutang' => $isHutang,
                ],
                'issues' => $issues, 'fingerprint' => $fp,
                'status' => BfjRealCommon::any($issues, fn ($i) => $i['severity'] === 'ERROR') ? 'ERROR' : ($issues === [] ? 'READY' : 'WARNING'),
                'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $layout['span'][0], $layout['span'][1]),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'sisa deposit']];
    }

    public static function retailCash(array $layout, array $grid): array
    {
        $rows = [];
        [$c1] = $layout['span'];
        $cols = BfjRealCommon::findCols($layout['headers'], 'TANGGAL', 'UANG RITEL');
        $cDate = $cols['TANGGAL'] ?? $c1;
        $cAmt = $cols['UANG RITEL'] ?? $c1 + 1;
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            $row = $grid[$rn];
            $dateRaw = BfjRealCommon::v($row, $cDate);
            $amtRaw = BfjRealCommon::v($row, $cAmt);
            // block subtotal rows (Total + amount in the next column) → benchmark
            if (mb_strtoupper($dateRaw) === 'TOTAL') {
                $tot = BfjRealCommon::money(BfjRealCommon::v($row, $cAmt + 1));
                if ($tot !== null) {
                    $fp = BfjFingerprinter::row(['ritelblock' => $rn, 'amt' => $tot]);
                    $rows[] = [
                        'normalized' => ['dimension' => 'RITEL_BLOCK_TOTAL|R'.$rn, 'date' => null, 'amount' => $tot, 'flow_type' => 'RETAIL_BLOCK'],
                        'issues' => [], 'fingerprint' => $fp, 'status' => 'READY',
                        'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $layout['span'][0], $layout['span'][1]),
                        'row' => $rn,
                    ];
                }

                continue;
            }
            $d = BfjRealCommon::dateCell($dateRaw);
            $amt = BfjRealCommon::money($amtRaw);
            $dAmt = BfjRealCommon::money(BfjRealCommon::v($row, $cAmt + 1));
            // dated text rows with an amount beside them are retail cash OUTFLOWS
            // (Pak Lasen, BBM Motor CRF, Nasi Blasting, ...) — kept negative, never income
            if ($d['value'] !== null && $amt === null && $amtRaw !== '' && $dAmt !== null) {
                $fp = BfjFingerprinter::row(['ritelout' => $d['value'], 'n' => $amtRaw, 'a' => $dAmt]);
                $rows[] = [
                    'normalized' => ['dimension' => 'RITEL_OUT|'.$d['value'].'|'.$amtRaw, 'date' => $d['value'], 'amount' => -$dAmt, 'flow_type' => 'RETAIL_OUT', 'notes' => $amtRaw],
                    'issues' => [BfjRealCommon::issue('RETAIL_OUTFLOW', 'WARNING', "R{$rn}: pengeluaran ritel '{$amtRaw}' {$dAmt} — bukan pemasukan")],
                    'fingerprint' => $fp, 'status' => 'WARNING',
                    'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $layout['span'][0], $layout['span'][1]),
                    'row' => $rn,
                ];

                continue;
            }
            if ($d['value'] === null && $amt === null) {
                // note rows without cash movement (e.g. "Proposal 17 agustus") are not income
                if ($dateRaw !== '' || $amtRaw !== '') {
                    $rows[] = [
                        'normalized' => ['dimension' => 'RITEL_NOTE|R'.$rn, 'date' => $d['value'], 'amount' => null, 'flow_type' => 'NOTE', 'notes' => $dateRaw.' '.$amtRaw],
                        'issues' => [BfjRealCommon::issue('NON_CASH_NOTE', 'WARNING', "R{$rn}: baris catatan tanpa kas — dilewati, review manual")],
                        'fingerprint' => BfjFingerprinter::row(['ritelnote' => $rn, 'v' => $dateRaw.$amtRaw]),
                        'status' => 'SKIPPED', 'posting_effect' => 'NONE',
                        'source' => BfjRealCommon::source($row, $layout['span'][0], $layout['span'][1]),
                        'row' => $rn,
                    ];
                }

                continue;
            }
            $issues = [];
            if ($d['error']) {
                $issues[] = BfjRealCommon::issue($d['error'], 'ERROR', "R{$rn}: tanggal ritel invalid");
            }
            if ($amt === null) {
                // dated row without any amount: incomplete entry, needs review (not silent)
                $issues[] = BfjRealCommon::issue('MISSING_FIELD', 'WARNING', "R{$rn}: nominal ritel kosong — perlu review");
            }
            $fp = BfjFingerprinter::row(['ritel' => $d['value'] ?? '', 'amt' => $amt, 'd' => BfjRealCommon::v($row, $cAmt + 1)]);
            $rows[] = [
                'normalized' => ['dimension' => 'RITEL_CASH|'.($d['value'] ?? '?'), 'date' => $d['value'], 'amount' => $amt, 'flow_type' => 'RETAIL_CASH'],
                'issues' => $issues, 'fingerprint' => $fp,
                'status' => BfjRealCommon::any($issues, fn ($i) => $i['severity'] === 'ERROR') ? 'ERROR' : ($issues === [] ? 'READY' : 'WARNING'),
                'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $layout['span'][0], $layout['span'][1]),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'uang ritel']];
    }

    /**
     * Customer delivery/deposit sheets. $sheetCustomer from tab name
     * (RITEL TF keeps its own name as customer).
     */
    public static function customer(array $layout, array $grid, string $sheetCustomer, ?string $periodLabel = null): array
    {
        $rows = [];
        [$c1, $c2] = $layout['span'];
        $cols = BfjRealCommon::findCols($layout['headers'], 'TANGGAL', 'NO DO', 'SOPIR', 'NO POL', 'MATERIAL', 'PENJUALAN', 'DEPOSIT', 'KET');
        $cDate = $cols['TANGGAL'] ?? $c1;
        $cDo = $cols['NO DO'] ?? $c1 + 1;
        $cDriver = $cols['SOPIR'] ?? $c1 + 2;
        $cPlate = $cols['NO POL'] ?? $c1 + 3;
        $cMat = $cols['MATERIAL'] ?? $c1 + 4;
        $cJual = $cols['PENJUALAN'] ?? $c1 + 5;
        $cDep = $cols['DEPOSIT'] ?? $c1 + 6;
        $cKet = $cols['KET'] ?? $c2;
        $rowNums = array_keys($grid);
        sort($rowNums);
        // Ref rows just under the header carry the material NAME (text in the
        // MATERIAL column) and the unit PRICE (numeric). Data rows carry the
        // VOLUME (m3) in the MATERIAL column and the amount in PENJUALAN.
        $unitPrice = null;
        $matName = null;
        $lastDate = null;
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row'] || $rn > $layout['header_row'] + 4) {
                continue;
            }
            // ref rows have no date/DO/driver of their own
            if (BfjRealCommon::v($grid[$rn], $cDate) !== '' || BfjRealCommon::v($grid[$rn], $cDo) !== '' || BfjRealCommon::v($grid[$rn], $cDriver) !== '') {
                continue;
            }
            $pv = BfjRealCommon::v($grid[$rn], $cMat);
            if ($pv !== '' && ! is_numeric($pv) && $matName === null) {
                $matName = $pv;
            }
            if (is_numeric($pv) && (float) $pv > 0 && $unitPrice === null) {
                $unitPrice = (float) $pv;
            }
        }
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            // price/name ref rows (no date/DO/driver AND no amounts) are not transactions —
            // but opening-balance rows carrying deposit values are real events
            $jualPeek = BfjRealCommon::money(BfjRealCommon::v($grid[$rn], $cJual));
            $depPeek = BfjRealCommon::money(BfjRealCommon::v($grid[$rn], $cDep));
            if ($rn <= $layout['header_row'] + 4
                && BfjRealCommon::v($grid[$rn], $cDate) === ''
                && BfjRealCommon::v($grid[$rn], $cDo) === ''
                && BfjRealCommon::v($grid[$rn], $cDriver) === ''
                && ($jualPeek === null || $jualPeek == 0) && ($depPeek === null || $depPeek == 0)) {
                continue;
            }
            if ($layout['stop_row'] !== null && $rn >= $layout['stop_row']) {
                break;
            }
            $row = $grid[$rn];
            $dateRaw = BfjRealCommon::v($row, $cDate);
            $do = BfjRealCommon::v($row, $cDo);
            $driver = BfjRealCommon::v($row, $cDriver);
            $plate = BfjRealCommon::v($row, $cPlate);
            $matCell = BfjRealCommon::v($row, $cMat);
            $jual = BfjRealCommon::money(BfjRealCommon::v($row, $cJual));
            $dep = BfjRealCommon::money(BfjRealCommon::v($row, $cDep));
            $ket = BfjRealCommon::v($row, $cKet);
            // volume lives in the MATERIAL column; a text value there is unexpected in data rows
            $volume = is_numeric($matCell) ? (float) $matCell : null;
            $matText = ($matCell !== '' && ! is_numeric($matCell)) ? $matCell : $matName;
            if ($dateRaw === '' && $do === '' && $driver === '' && $plate === '' && $matCell === '' && ($jual === null || $jual == 0) && ($dep === null || $dep == 0) && $ket === '') {
                continue; // empty or formula-zero separator rows are structural, not data
            }
            // merged header echoes (TANGGAL/NO DO/NAMA SOPIR filled into ref rows) are not data
            if (mb_strtoupper($dateRaw) === 'TANGGAL' || mb_strtoupper($do) === 'NO DO' || mb_strtoupper($driver) === 'NAMA SOPIR') {
                continue;
            }
            $issues = [];
            if ($matName === null && $matText === null) {
                $issues[] = BfjRealCommon::issue('MISSING_FIELD', 'WARNING', "R{$rn}: nama material tidak ada di baris referensi sheet");
            }
            $d = BfjRealCommon::dateCell($dateRaw);
            $date = $d['value'];
            // KET often carries the event date (text "3 Agustus 2026" or serial)
            $ketDate = null;
            if ($ket !== '') {
                $kd = BfjRealCommon::dateCell($ket);
                if ($kd['value'] === null && is_numeric($ket)) {
                    $kd = BfjRealCommon::dateCell((float) $ket);
                }
                $ketDate = $kd['value'];
            }
            // deposit-only rows carry the date inside KET
            if ($date === null && ($jual === null || $driver === '') && $dep !== null && $ketDate !== null) {
                $date = $ketDate;
                $issues[] = BfjRealCommon::issue('DATE_FROM_KET', 'WARNING', "R{$rn}: tanggal diambil dari KET");
            }
            $ketU0 = mb_strtoupper($ket);
            $isOpeningHint = str_contains($ketU0, 'SISA') || str_contains($ketU0, 'LAMA') || str_contains($ketU0, 'AWAL');
            if ($date === null) {
                // merged-date rows are forward-filled; unmerged continuations inherit
                // the previous dated row of the same table (flagged, never silent)
                if ($lastDate !== null && ($do !== '' || $driver !== '' || $jual !== null)) {
                    $date = $lastDate;
                    $issues[] = BfjRealCommon::issue('DATE_FORWARD_FILLED', 'WARNING', "R{$rn}: tanggal mengikuti baris {$lastDate} di atasnya — verifikasi");
                } elseif (($do !== '' || $driver !== '' || $jual !== null) && ! ($isOpeningHint && $dep !== null)) {
                    $issues[] = BfjRealCommon::issue($d['error'] ?? 'MISSING_FIELD', 'ERROR', "R{$rn}: tanggal kosong pada baris delivery");
                } elseif (! ($isOpeningHint && $dep !== null)) {
                    continue;
                } else {
                    $issues[] = BfjRealCommon::issue('MISSING_FIELD', 'WARNING', "R{$rn}: saldo awal tanpa tanggal — dipertahankan tanpa tanggal");
                }
            } elseif ($periodLabel && BfjParsers::periodMismatch($date, $periodLabel)) {
                $issues[] = BfjRealCommon::issue('PERIOD_MISMATCH', 'WARNING', "R{$rn}: tanggal {$date} di luar periode {$periodLabel}");
            }
            if ($ketDate !== null && $date !== null && $ketDate !== $date) {
                $issues[] = BfjRealCommon::issue('DATE_MISMATCH', 'WARNING', "R{$rn}: tanggal baris {$date} vs KET {$ketDate} — keduanya dipertahankan");
            }
            if ($date !== null) {
                $lastDate = $date;
            }
            $type = 'DEPOSIT';
            if (($jual ?? 0) > 0) {
                $type = 'CONSUMPTION';
            } elseif ($dep !== null && ($do !== '' || $driver !== '')) {
                $type = 'DEPOSIT';
            }
            $ketU = mb_strtoupper($ket);
            if (str_contains($ketU, 'SISA') && str_contains($ketU, 'LAMA') || str_contains($ketU, 'SALDO AWAL') || str_contains($ketU, 'SISA BULAN')) {
                $type = 'OPENING_BALANCE';
            }
            if (str_contains($ketU, 'REFUND')) {
                $type = 'REFUND';
            }
            if ($type === 'CONSUMPTION' && $volume === null) {
                $issues[] = BfjRealCommon::issue('MISSING_VOLUME', 'ERROR', "R{$rn}: volume kosong pada delivery");
            }
            $amount = $type === 'CONSUMPTION' ? ($jual ?? 0) : ($dep ?? 0);
            // cross-check amount vs volume × unit price where all three exist
            if ($type === 'CONSUMPTION' && $volume !== null && $unitPrice && $jual !== null && abs($jual - $volume * $unitPrice) > 1) {
                $issues[] = BfjRealCommon::issue('AMOUNT_VARIANCE', 'WARNING', "R{$rn}: PENJUALAN {$jual} vs vol×harga ".($volume * $unitPrice));
            }
            $normalized = [
                'date' => $date,
                'ket_date' => $ketDate,
                'legacy_do_number' => $do,
                'driver' => $driver,
                'vehicle' => $plate !== '' ? BfjNormalizer::normalizePlate($plate) : null,
                'vehicle_legacy' => $plate,
                'material' => $matText !== null ? BfjNormalizer::normalizeMaterial($matText) : null,
                'material_legacy' => $matText,
                'type' => $type,
                'amount' => $amount,
                'volume' => $volume,
                'unit_price_ref' => $unitPrice,
                'legacy_invoice' => preg_match('/INV\s*NO\s*\S+/i', $ket, $mm) ? mb_strtoupper(trim($mm[0])) : '',
                'notes' => $ket,
                'customer' => BfjNormalizer::normalizeCustomer($sheetCustomer),
                'customer_legacy' => $sheetCustomer,
            ];
            $normalized['fingerprint'] = BfjFingerprinter::deposit([
                'customer' => $sheetCustomer, 'date' => $date, 'reference' => $do,
                'amount' => $amount, 'material' => $normalized['material'] ?? '',
                'driver' => $driver, 'vehicle' => $plate, 'type' => $type,
            ]);
            $staged = [[
                'normalized' => $normalized, 'issues' => $issues,
                'fingerprint' => $normalized['fingerprint'],
                'status' => BfjRealCommon::any($issues, fn ($i) => $i['severity'] === 'ERROR') ? 'ERROR' : ($issues === [] ? 'READY' : 'WARNING'),
                'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $c1, $c2),
                'row' => $rn,
            ]];
            // a row carrying BOTH a delivery and a dated deposit holds two events:
            // split the deposit leg out instead of merging semantics
            if ($type === 'CONSUMPTION' && ($dep ?? 0) > 0 && $ketDate !== null && $ketDate !== $date) {
                $depNorm = $normalized;
                $depNorm['type'] = 'DEPOSIT';
                $depNorm['date'] = $ketDate;
                $depNorm['amount'] = $dep;
                $depNorm['volume'] = null;
                $depNorm['fingerprint'] = BfjFingerprinter::deposit([
                    'customer' => $sheetCustomer, 'date' => $ketDate, 'reference' => $do.'#DEP',
                    'amount' => $dep, 'material' => '', 'driver' => $driver, 'vehicle' => $plate, 'type' => 'DEPOSIT',
                ]);
                $staged[] = [
                    'normalized' => $depNorm,
                    'issues' => [BfjRealCommon::issue('SPLIT_EVENT', 'WARNING', "R{$rn}: deposit {$dep} dipisah ke {$ketDate} (delivery {$date})")],
                    'fingerprint' => $depNorm['fingerprint'], 'status' => 'WARNING',
                    'posting_effect' => 'NONE', 'source' => BfjRealCommon::source($row, $c1, $c2),
                    'row' => $rn,
                ];
            }
            foreach ($staged as $s) {
                $rows[] = $s;
            }
        }

        return ['rows' => $rows, 'action' => 'IMPORT', 'notes' => ['customer' => $sheetCustomer]];
    }

    /** SMJ monthly recap table: named per-month volume/amount/deposit/sisa. Column-restricted to the monthly table (cols at/after its Bulan header). */
    public static function smjMonthly(array $layout, array $grid): array
    {
        $rows = [];
        // monthly table starts at the NO|Bulan|... header row; find it and its column offset
        $startRow = null;
        $startCol = null;
        $mcols = [];
        foreach ($grid as $rn => $row) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            foreach ($row as $col => $cell) {
                if (mb_strtoupper(trim($cell['v'] ?? '')) === 'BULAN') {
                    $line = mb_strtoupper(implode('|', array_map(fn ($c) => trim($c['v'] ?? ''), $row)));
                    if (str_contains($line, 'KUBIKASI')) {
                        $startRow = $rn;
                        // monthly NO column sits just left of Bulan
                        $startCol = max(0, $col - 1);
                        foreach ($row as $cc => $hcell) {
                            if ($cc >= $startCol) {
                                $mcols[mb_strtoupper(trim($hcell['v'] ?? ''))] = $cc;
                            }
                        }
                        break 2;
                    }
                }
            }
        }
        if ($startRow === null) {
            return ['rows' => [], 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'smj monthly (header not found)']];
        }
        $cVol = $mcols['KUBIKASI (M3)'] ?? $mcols['KUBIKASI'] ?? null;
        $cAmt = $mcols['JUMLAH (RP)'] ?? $mcols['JUMLAH'] ?? null;
        $cDep = $mcols['DEPOSIT'] ?? null;
        $cSisa = $mcols['SISA DEPOSIT'] ?? null;
        $rowNums = array_keys($grid);
        sort($rowNums);
        $monthNames = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        foreach ($rowNums as $rn) {
            if ($rn <= $startRow) {
                continue;
            }
            $row = $grid[$rn];
            $cells = array_filter($row, fn ($col) => $col >= $startCol, ARRAY_FILTER_USE_KEY);
            $vals = array_values(array_map(fn ($c) => trim($c['v'] ?? ''), $cells));
            $line = mb_strtoupper(implode('|', $vals));
            if (str_contains($line, 'TOTAL')) {
                break;
            }
            $month = null;
            foreach ($monthNames as $mn) {
                if (str_contains($line, $mn)) {
                    $month = $mn;
                }
            }
            if ($month === null) {
                continue;
            }
            $norm = [
                'dimension' => 'SMJ_MONTHLY|'.$month, 'month' => $month,
                'volume' => $cVol !== null ? BfjRealCommon::money(BfjRealCommon::v($row, $cVol)) : null,
                'amount' => $cAmt !== null ? BfjRealCommon::money(BfjRealCommon::v($row, $cAmt)) : null,
                'deposit' => $cDep !== null ? BfjRealCommon::money(BfjRealCommon::v($row, $cDep)) : null,
                'sisa' => $cSisa !== null ? BfjRealCommon::money(BfjRealCommon::v($row, $cSisa)) : null,
            ];
            $fp = BfjFingerprinter::row(['smj' => $month, 'n' => implode(',', array_map(fn ($v) => (string) ($v ?? ''), [$norm['volume'], $norm['amount'], $norm['deposit'], $norm['sisa']]))]);
            $rows[] = [
                'normalized' => $norm,
                'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                'source' => BfjRealCommon::source($row, $startCol, $layout['span'][1]),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'smj monthly']];
    }
}
