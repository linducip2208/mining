<?php

namespace App\Services\Bfj;

use App\Support\SpreadsheetReader;

/**
 * Real-workbook table layout detector (§4).
 *
 * BFJ sheets never put headers on row 1: titles (R3), dates (R4),
 * price rows (R7-R8) and merged groups sit above the real header.
 * This detector finds the header row by signature scoring, slices the
 * transaction column span (side price tables and second ledgers are
 * excluded), forward-fills merged cells and marks TOTAL/stop rows.
 */
final class BfjSheetLayout
{
    /** header signature tokens per domain (uppercase substrings) */
    public const SIGNATURES = [
        'SALES' => ['NO DO', 'NAMA SOPIR', 'NO POLIS', 'COSTUMER', 'CUSTOMER', 'JENIS MATERIAL', 'KUBIKASI', 'HARGA', 'RITEL', 'REKENING'],
        'DEPOSIT' => ['TANGGAL', 'NO DO', 'NAMA SOPIR', 'NO POL', 'MATERIAL', 'PENJUALAN', 'DEPOSIT'],
        'PAYROLL' => ['TANGGAL', 'JAM NORMAL', 'LEMBUR', 'MULAI', 'SELESAI', 'STAND', 'KEGIATAN'],
        'FINANCE' => ['TANGGAL', 'TIPE', 'NOMOR NOTA', 'DESKRIPSI', 'QTY', 'HARGA SATUAN', 'TOTAL'],
        'SPAREPART' => ['KODE SPAREPART', 'KODE', 'NAMA SPAREPART', 'SATUAN', 'QYT MASUK', 'QTY MASUK', 'QTY KELUAR', 'PEMAKAI', 'KEPERLUAN', 'SUPPLIER', 'PENERIMA'],
        'DOCUMENT' => ['NOMOR SURAT', 'NOMOR INVOICE', 'NOMOR KWITANSI', 'NAMA CUSTOMER', 'KUBIKASI', 'PRODUK', 'PERIHAL', 'TUJUAN'],
        'SISA' => ['SISA DEPOSIT', 'JENIS MATERIAL', 'NAMA'],
        'RETAIL_CASH' => ['UANG RITEL', 'TANGGAL'],
        'PAYROLL_RECAP' => ['JABATAN', 'UPAH', 'KASBON', 'TIDAK HADIR', 'UPAH NETTO', 'NO REKENING', 'NAMA BANK'],
        'SECURITY' => ['JUMLAH HK', 'NAMA'],
        'STOCK' => ['STOK MASUK', 'STOK KELUAR', 'SALDO STOK', 'STOK SISTEM', 'STOK FISIK', 'STOK AWAL', 'STOK AKHIR'],
    ];

    public const MATERIALS = ['ABU BATU', 'SPLIT', 'AGREGAT', 'BATU BELAH', 'BLASTING', 'QUARRY', 'BATU BLASTING'];

    /**
     * @param  array<int, array<int, array{v:string,f:string|null,cached:bool}>>  $grid
     * @param  string[]  $merges
     * @return array{header_row:int|null,group_row:int|null,span:array{0:int,1:int},headers:array<int,string>,groups:array<int,string>,total_rows:int[],stop_row:int|null,title:array<string,string|null>,score:int,domain:string}
     */
    public static function analyze(array $grid, array $merges, string $sheetName = ''): array
    {
        $grid = self::forwardFill($grid, $merges);
        $rowNums = array_keys($grid);
        sort($rowNums);
        $best = ['row' => null, 'score' => 0, 'domain' => ''];
        foreach (array_slice($rowNums, 0, 30) as $rn) {
            [$score, $domain] = self::scoreRow($grid[$rn] ?? []);
            if ($score > $best['score']) {
                $best = ['row' => $rn, 'score' => $score, 'domain' => $domain];
            }
        }
        if ($best['row'] === null || $best['score'] < 2) {
            return [
                'header_row' => null, 'group_row' => null, 'span' => [0, 0], 'headers' => [], 'groups' => [],
                'total_rows' => [], 'stop_row' => null, 'title' => self::title($grid, null),
                'score' => $best['score'], 'domain' => $best['domain'],
            ];
        }
        $hr = $best['row'];
        // payroll-style grouped header: sub row (Mulai/Selesai/Jml) with group row above
        $groupRow = null;
        $vals = self::rowValues($grid[$hr] ?? []);
        if (self::isSubHeader($vals) && isset($grid[$hr - 1])) {
            $groupRow = $hr - 1;
        }
        // span: first..last non-empty header cell
        $nonEmpty = [];
        foreach ($grid[$hr] as $col => $cell) {
            if (trim($cell['v']) !== '') {
                $nonEmpty[] = $col;
            }
        }
        // include formula-only header cells adjacent (merged group tails)
        $span = [$nonEmpty === [] ? 0 : min($nonEmpty), $nonEmpty === [] ? 0 : max($nonEmpty)];
        $headers = self::buildHeaders($grid, $hr, $groupRow, $span);
        // delivery layouts: truncate the span after the last notes column
        // (KET / KETERANGAN MATERIAL) so side ledgers sharing the same rows
        // (PAK RAHMAT daily hauling, SMJ monthly, price tables, recap matrix)
        // never bleed into the transaction table
        if (in_array($best['domain'], ['SALES', 'DEPOSIT'], true)) {
            $notesCol = null;
            foreach ($headers as $col => $name) {
                $u = mb_strtoupper($name);
                if ($u === 'KET' || str_starts_with($u, 'KET ') || $u === 'KETERANGAN' || $u === 'KETERANGAN MATERIAL') {
                    $notesCol = $col;
                }
            }
            if ($notesCol !== null && $notesCol < $span[1]) {
                $span[1] = $notesCol;
                $headers = self::buildHeaders($grid, $hr, $groupRow, $span);
            }
        }
        $groups = [];
        if ($groupRow !== null) {
            $groups = self::buildGroups($grid, $groupRow, $span);
        }
        // TOTAL rows + stop row: a standalone TOTAL label in ANY span cell ends
        // detail (labels often sit under COSTUMER, not the lead column)
        $totalRows = [];
        $stopRow = null;
        foreach ($rowNums as $rn) {
            if ($rn <= $hr) {
                continue;
            }
            $hasTotal = false;
            for ($c = $span[0]; $c <= $span[1]; $c++) {
                if (mb_strtoupper(trim((string) ($grid[$rn][$c]['v'] ?? ''))) === 'TOTAL') {
                    $hasTotal = true;
                    break;
                }
            }
            if ($hasTotal) {
                $totalRows[] = $rn;
                $stopRow ??= $rn;
            }
        }

        return [
            'header_row' => $hr, 'group_row' => $groupRow, 'span' => $span, 'headers' => $headers, 'groups' => $groups,
            'total_rows' => $totalRows, 'stop_row' => $stopRow, 'title' => self::title($grid, $hr),
            'score' => $best['score'], 'domain' => $best['domain'],
        ];
    }

    /**
     * Fill empty merged cells from their top-left donor (dates merged over
     * day blocks). Creates missing cells: raw XML omits empty cells, so a
     * donor map over existing cells alone cannot reach them.
     *
     * Only single-column merges are filled: multi-column merges are layout
     * formatting (B13:D14 value blocks) and filling them would duplicate
     * values across columns. Group headers still resolve via carry-forward.
     */
    public static function forwardFill(array $grid, array $merges): array
    {
        foreach ($merges as $ref) {
            if (! str_contains($ref, ':')) {
                continue;
            }
            [$a, $b] = explode(':', $ref, 2);
            [$c1, $r1] = SpreadsheetReader::splitRef($a);
            [$c2, $r2] = SpreadsheetReader::splitRef($b);
            $tc = min($c1, $c2);
            if (max($c1, $c2) !== $tc) {
                continue;
            }
            $tr = min($r1, $r2);
            $donor = $grid[$tr][$tc] ?? null;
            if ($donor === null || trim((string) ($donor['v'] ?? '')) === '') {
                continue;
            }
            for ($r = min($r1, $r2); $r <= max($r1, $r2); $r++) {
                for ($c = $tc; $c <= max($c1, $c2); $c++) {
                    if ($r === $tr && $c === $tc) {
                        continue;
                    }
                    $cur = $grid[$r][$c] ?? null;
                    if ($cur === null || (trim((string) ($cur['v'] ?? '')) === '' && ($cur['f'] ?? null) === null)) {
                        $grid[$r][$c] = ['v' => $donor['v'], 'f' => null, 'cached' => true, 'filled' => true];
                    }
                }
            }
        }

        return $grid;
    }

    /** @return array{0:int,1:string} */
    private static function scoreRow(array $cols): array
    {
        $joined = ' | '.mb_strtoupper(implode(' | ', array_map(fn ($c) => $c['v'] ?? '', $cols))).' | ';
        $best = [0, ''];
        foreach (self::SIGNATURES as $domain => $tokens) {
            $hits = 0;
            foreach ($tokens as $t) {
                if (str_contains($joined, $t)) {
                    $hits++;
                }
            }
            if ($hits > $best[0]) {
                $best = [$hits, $domain];
            }
        }
        // recap matrix: TANGGAL + >=3 material tokens
        $matHits = 0;
        foreach (self::MATERIALS as $m) {
            if (str_contains($joined, $m)) {
                $matHits++;
            }
        }
        if (str_contains($joined, 'TANGGAL') && $matHits >= 3 && $best[0] < 4) {
            $best = [4, 'MATRIX'];
        }

        return $best;
    }

    /** @param  array<int, array{v:string}>  $cols */
    private static function rowValues(array $cols): array
    {
        ksort($cols);

        return array_map(fn ($c) => mb_strtoupper(trim($c['v'] ?? '')), $cols);
    }

    /** @param  string[]  $vals */
    private static function isSubHeader(array $vals): bool
    {
        $j = implode(' ', $vals);

        return str_contains($j, 'MULAI') && str_contains($j, 'SELESAI') && str_contains($j, 'JML');
    }

    /** @return array<int,string> */
    private static function buildHeaders(array $grid, int $hr, ?int $groupRow, array $span): array
    {
        $headers = [];
        $seen = [];
        for ($c = $span[0]; $c <= $span[1]; $c++) {
            $v = trim((string) ($grid[$hr][$c]['v'] ?? ''));
            if ($v === '') {
                $v = 'COL_'.SpreadsheetReader::columnName($c);
            }
            $u = mb_strtoupper($v);
            if (isset($seen[$u])) {
                $seen[$u]++;
                $v = $v.'#'.$seen[$u];
                $u = mb_strtoupper($v);
            } else {
                $seen[$u] = 1;
            }
            $headers[$c] = $v;
        }

        return $headers;
    }

    /** @return array<int,string> group label per column (merged group row, forward-filled) */
    private static function buildGroups(array $grid, int $gr, array $span): array
    {
        $groups = [];
        $last = '';
        for ($c = $span[0]; $c <= $span[1]; $c++) {
            $v = trim((string) ($grid[$gr][$c]['v'] ?? ''));
            if ($v !== '') {
                $last = $v;
            }
            $groups[$c] = $last;
        }

        return $groups;
    }

    /** @return array<string,string|null> */
    private static function title(array $grid, ?int $headerRow): array
    {
        $out = ['company' => null, 'date' => null, 'period' => null];
        foreach ($grid as $rn => $cols) {
            if ($headerRow !== null && $rn >= $headerRow) {
                break;
            }
            $line = mb_strtoupper(implode(' ', array_map(fn ($c) => trim($c['v'] ?? ''), $cols)));
            if ($line === '') {
                continue;
            }
            if ($out['company'] === null && (str_contains($line, 'PT') || str_contains($line, 'BFJ') || str_contains($line, 'FAMILLY') || str_contains($line, 'BARUS'))) {
                $out['company'] = trim(implode(' ', array_filter(array_map(fn ($c) => trim($c['v'] ?? ''), $cols))));
            }
            if ($out['date'] === null && str_contains($line, 'TANGGAL') && preg_match('/\d{4}/', $line)) {
                $out['date'] = trim(implode(' ', array_filter(array_map(fn ($c) => trim($c['v'] ?? ''), $cols))));
            }
            if ($out['period'] === null && str_contains($line, 'PERIODE')) {
                $out['period'] = trim(implode(' ', array_filter(array_map(fn ($c) => trim($c['v'] ?? ''), $cols))));
            }
        }

        return $out;
    }
}
