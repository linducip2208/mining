<?php

namespace App\Services\Bfj;

/**
 * Real Penjualan September 2026 parsers (§7-§12).
 *
 * Daily sheets: header R6 (blank JUMLAH header possible), data until TOTAL
 * row, sheet date from title ("Tanggal 1 September 2026").
 * Amount columns (RITEL / REKENING ALASEN / REKENING PERUSAHAAN) hold
 * structured-table formulas WITHOUT cached values, so:
 *   channel = which of the three columns carries formula-or-value
 *   amount  = ERP recompute volume × unit_price (status RECOMPUTED)
 * When a cached value exists it is kept and compared instead.
 */
final class BfjRealSalesParser
{
    /**
     * @param  array{header_row:int,span:array{0:int,1:int},headers:array<int,string>,title:array<string,string|null>}  $layout
     * @param  array<int, array<int, array{v:string,f:string|null,cached:bool}>>  $grid
     */
    public static function daily(array $layout, array $grid, string $sheetName, float $tolerance = 1.0): array
    {
        $rows = [];
        [$c1, $c2] = $layout['span'];
        $cols = BfjRealCommon::findCols($layout['headers'], 'NO DO', 'SOPIR', 'POLIS', 'COSTUMER', 'CUSTOMER', 'JENIS MATERIAL', 'MATERIAL', 'KUBIKASI', 'HARGA', 'RITEL', 'ALASEN', 'PERUSAHAAN', 'KETERANGAN MATERIAL', 'KET');
        $cDo = $cols['NO DO'] ?? $c1;
        $cDriver = $cols['SOPIR'] ?? $c1 + 1;
        $cPlate = $cols['POLIS'] ?? $c1 + 2;
        $cCust = $cols['COSTUMER'] ?? $cols['CUSTOMER'] ?? $c1 + 3;
        $cMat = $cols['JENIS MATERIAL'] ?? $cols['MATERIAL'] ?? $c1 + 4;
        $cVol = $cols['KUBIKASI'] ?? $c1 + 5;
        $cPrice = $cols['HARGA'] ?? $c1 + 6;
        $cRitel = $cols['RITEL'] ?? null;
        $cAlasen = $cols['ALASEN'] ?? null;
        $cPerus = $cols['PERUSAHAAN'] ?? null;
        $cKet = $cols['KETERANGAN MATERIAL'] ?? $cols['KET'] ?? $c2;
        $sheetDate = BfjRealCommon::titleDay($layout['title']['date'] ?? null)
            ?? BfjRealCommon::titleDay($sheetName);

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
            $lead = BfjRealCommon::v($row, $cDo);
            if ($lead === '' && BfjRealCommon::isBlankRow($row, $c1, $c2)) {
                continue;
            }
            // merged header echoes are not data
            if (mb_strtoupper($lead) === 'NO DO') {
                continue;
            }
            // matrix/recap rows below data have non-numeric lead → end of detail
            if ($lead !== '' && ! is_numeric($lead)) {
                break;
            }
            if ($lead === '' && BfjRealCommon::v($row, $cDriver) === '' && BfjRealCommon::v($row, $cVol) === '') {
                continue;
            }
            $issues = [];
            $vol = BfjRealCommon::qty(BfjRealCommon::v($row, $cVol));
            $price = BfjRealCommon::money(BfjRealCommon::v($row, $cPrice));
            if ($vol === null) {
                $issues[] = BfjRealCommon::issue('MISSING_VOLUME', 'ERROR', "R{$rn}: kubikasi kosong/tidak angka");
            }
            if ($price === null) {
                $issues[] = BfjRealCommon::issue('MISSING_PRICE', 'ERROR', "R{$rn}: harga kosong/tidak angka");
            }
            // channel: which amount column carries formula-or-value
            $chanMarks = [];
            foreach (['CASH' => $cRitel, 'PERSONAL_CLEARING' => $cAlasen, 'COMPANY_BANK' => $cPerus] as $ch => $cc) {
                if ($cc === null) {
                    continue;
                }
                $hasV = BfjRealCommon::v($row, $cc) !== '';
                $hasF = BfjRealCommon::hasFormula($row, $cc);
                if ($hasV || $hasF) {
                    $chanMarks[$ch] = ['v' => BfjRealCommon::money(BfjRealCommon::v($row, $cc)), 'cached' => $hasV];
                }
            }
            if (count($chanMarks) > 1) {
                $issues[] = BfjRealCommon::issue('AMOUNT_VARIANCE', 'WARNING', "R{$rn}: >1 kolom bayar terisi — ambil total gabungan");
            }
            $channel = array_key_first($chanMarks) ?? 'CASH';
            $recorded = array_sum(array_map(fn ($m) => $m['v'] ?? 0, $chanMarks));
            $hasCache = BfjRealCommon::any($chanMarks, fn ($m) => $m['cached']);
            $expected = ($vol ?? 0) * ($price ?? 0);
            if ($hasCache && $expected > 0 && abs($recorded - $expected) > $tolerance) {
                $st = abs($recorded - $expected) < max(1000, $expected * 0.005) ? 'ROUNDING_VARIANCE' : 'FORMULA_VARIANCE';
                $issues[] = BfjRealCommon::issue('AMOUNT_VARIANCE', 'WARNING', "R{$rn}: vol×harga {$expected} vs tercatat {$recorded} ({$st})");
                $status = $st;
                $gross = $recorded;
            } elseif (! $hasCache && $expected > 0) {
                $status = 'RECOMPUTED';
                $gross = $expected;
                $issues[] = BfjRealCommon::issue('FORMULA_NO_CACHE', 'WARNING', "R{$rn}: formula tanpa cached value — ERP hitung ulang vol×harga");
            } else {
                $status = $hasCache ? 'MATCH' : 'RECOMPUTED';
                $gross = $hasCache ? $recorded : $expected;
            }
            $driver = BfjRealCommon::v($row, $cDriver);
            $plateRaw = BfjRealCommon::v($row, $cPlate);
            $custRaw = BfjRealCommon::v($row, $cCust);
            $matRaw = BfjRealCommon::v($row, $cMat);
            $normalized = [
                'transaction_date' => $sheetDate,
                'legacy_do_number' => $lead,
                'driver' => $driver,
                'vehicle' => $plateRaw !== '' ? BfjNormalizer::normalizePlate($plateRaw) : null,
                'vehicle_legacy' => $plateRaw,
                'customer' => $custRaw !== '' ? BfjNormalizer::normalizeCustomer($custRaw) : null,
                'customer_legacy' => $custRaw,
                'material' => $matRaw !== '' ? BfjNormalizer::normalizeMaterial($matRaw) : null,
                'material_legacy' => $matRaw,
                'volume_m3' => $vol,
                'unit_price' => $price,
                'gross_amount' => $gross,
                'expected_amount' => $expected,
                'amount_status' => $status,
                'payment_channel' => $channel,
                'notes' => BfjRealCommon::v($row, $cKet),
            ];
            $normalized['fingerprint'] = BfjFingerprinter::sales([
                'transaction_date' => $sheetDate, 'legacy_do_number' => $lead,
                'customer' => $custRaw, 'vehicle' => $plateRaw, 'material' => $matRaw,
                'volume_m3' => $vol, 'gross_amount' => $gross,
            ]);
            $err = BfjRealCommon::any($issues, fn ($i) => $i['severity'] === 'ERROR');
            $rows[] = [
                'normalized' => $normalized, 'issues' => $issues,
                'fingerprint' => $normalized['fingerprint'],
                'status' => $err ? 'ERROR' : ($issues === [] ? 'READY' : 'WARNING'),
                'posting_effect' => 'NONE',
                'source' => BfjRealCommon::source($row, $c1, min($c2, $cKet)),
                'row' => $rn,
            ];
        }

        return ['rows' => $rows, 'action' => 'IMPORT', 'notes' => ['sheet_date' => $sheetDate]];
    }

    /**
     * REKAP BFJ / REKAP MAA volume matrix: serial-date rows × material columns.
     *
     * @param  array{header_row:int,span:array{0:int,1:int},headers:array<int,string>}  $layout
     */
    public static function recapMatrix(array $layout, array $grid): array
    {
        $rows = [];
        [$c1, $c2] = $layout['span'];
        $matCols = [];
        foreach ($layout['headers'] as $col => $name) {
            $u = mb_strtoupper($name);
            if ($u === 'TANGGAL' || $u === 'TOTAL (M3)' || str_starts_with($u, 'COL_')) {
                continue;
            }
            $matCols[$col] = BfjNormalizer::normalizeMaterial($name);
        }
        $cDate = null;
        foreach ($layout['headers'] as $col => $name) {
            if (mb_strtoupper($name) === 'TANGGAL') {
                $cDate = $col;
            }
        }
        $cDate ??= $c1;
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row']) {
                continue;
            }
            $row = $grid[$rn];
            $d = BfjRealCommon::dateCell(BfjRealCommon::v($row, $cDate));
            if ($d['value'] === null) {
                continue; // empty future-date rows
            }
            foreach ($matCols as $col => $mat) {
                $vol = BfjRealCommon::qty(BfjRealCommon::v($row, $col));
                if ($vol === null || abs($vol) < 0.0001) {
                    continue;
                }
                $fp = BfjFingerprinter::row(['recap' => $d['value'], 'mat' => $mat, 'vol' => $vol]);
                $rows[] = [
                    'normalized' => ['dimension' => $d['value'].'|'.$mat, 'date' => $d['value'], 'material' => $mat, 'volume_total' => $vol],
                    'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                    'source' => BfjRealCommon::source($row, $c1, $c2),
                    'row' => $rn,
                ];
            }
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'volume matrix']];
    }

    /**
     * REKAP RITEL / REKAP DEPOSIT wide matrix: date + customer + per-material volumes.
     */
    public static function retailMatrix(array $layout, array $grid): array
    {
        $rows = [];
        [$c1, $c2] = $layout['span'];
        // sub-material row sits right below header (A-B, 1/1, ...); same row may BE header if detector picked it
        $matCols = [];
        foreach ($layout['headers'] as $col => $name) {
            $u = mb_strtoupper(trim($name));
            $canon = BfjNormalizer::normalizeMaterial(str_replace(['Column113', 'Column112'], ['B-B', 'QW'], $u));
            if (in_array($u, ['TANGGAL', 'DEPOSIT', 'TOTAL', 'KET', 'TOTAL (M3)'], true) || str_starts_with($u, 'COLUMN')) {
                continue;
            }
            $matCols[$col] = $canon !== $u && $canon !== '' ? $canon : null;
        }
        // fallback: read material codes from the row under the header
        if (count($matCols) < 3) {
            $hr = $layout['header_row'];
            $sub = $grid[$hr + 1] ?? [];
            $codeMap = ['A-B' => 'ABU BATU', '1/1' => 'SPLIT 1/1', '1/2' => 'SPLIT 1/2', '2/3' => 'SPLIT 2/3', '3/5' => 'SPLIT 3/5', '5/7' => 'SPLIT 5/7', 'AGR A' => 'AGREGAT A', 'AGR B' => 'AGREGAT B', 'B-B' => 'BATU BELAH', 'BLASTING' => 'BLASTING', 'QW' => 'QUARRY WES'];
            foreach ($sub as $col => $cell) {
                $code = mb_strtoupper(trim($cell['v'] ?? ''));
                if (isset($codeMap[$code])) {
                    $matCols[$col] = $codeMap[$code];
                }
            }
        }
        $cDate = $c1;
        $cCust = $c1 + 1;
        $rowNums = array_keys($grid);
        sort($rowNums);
        foreach ($rowNums as $rn) {
            if ($rn <= $layout['header_row'] + 1) {
                continue;
            }
            $row = $grid[$rn];
            $d = BfjRealCommon::dateCell(BfjRealCommon::v($row, $cDate));
            $cust = BfjRealCommon::v($row, $cCust);
            if ($d['value'] === null && $cust === '') {
                continue;
            }
            foreach ($matCols as $col => $mat) {
                if (! $mat) {
                    continue;
                }
                $vol = BfjRealCommon::qty(BfjRealCommon::v($row, $col));
                if ($vol === null || abs($vol) < 0.0001) {
                    continue;
                }
                $fp = BfjFingerprinter::row(['recap' => $d['value'] ?? '', 'cust' => $cust, 'mat' => $mat, 'vol' => $vol]);
                $rows[] = [
                    'normalized' => ['dimension' => ($d['value'] ?? '?').'|'.$cust.'|'.$mat, 'date' => $d['value'], 'customer' => $cust !== '' ? BfjNormalizer::normalizeCustomer($cust) : null, 'material' => $mat, 'volume_total' => $vol],
                    'issues' => [], 'fingerprint' => $fp, 'status' => 'READY', 'posting_effect' => 'NONE',
                    'source' => BfjRealCommon::source($row, $c1, $c2),
                    'row' => $rn,
                ];
            }
        }

        return ['rows' => $rows, 'action' => 'RECONCILE_ONLY', 'notes' => ['benchmark' => 'retail/deposit matrix']];
    }
}
