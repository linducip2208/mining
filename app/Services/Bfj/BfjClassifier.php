<?php

namespace App\Services\Bfj;

/**
 * Workbook + sheet classifier (§3). Scores filename, sheet names,
 * headers and content signatures. Low confidence → ask user.
 */
final class BfjClassifier
{
    public const TYPES = [
        'FINANCE', 'SALES', 'CUSTOMER_DEPOSIT', 'PAYROLL', 'CORRESPONDENCE',
        'INVOICE_REGISTER', 'RECEIPT_REGISTER', 'SPAREPART_MASTER', 'SPAREPART_OPENING',
        'SPAREPART_ISSUE', 'STOCK_CARD', 'STOCK_OPNAME', 'STOCK_REPORT',
        'SALES_RECAP_MATRIX', 'SALES_RETAIL_MATRIX', 'DEPOSIT_SISA', 'DEPOSIT_RETAIL_CASH',
        'FINANCE_DETAIL', 'FINANCE_STATEMENT', 'PAYROLL_DAY', 'PAYROLL_RECAP', 'PAYROLL_SECURITY',
        'LOADING_LOG', 'CUSTOMER_STOCK', 'EMPTY',
    ];

    /** Sheet-name rules take precedence (real BFJ tab names are stable). */
    public static function sheetNameType(string $sheetName): ?array
    {
        $u = mb_strtoupper(trim($sheetName));
        if ($u === 'SLIP GAJI' || $u === 'SHEET9') {
            return ['type' => 'EMPTY', 'confidence' => 95, 'is_summary' => false];
        }
        if ($u === '' || $u === 'SHEET1' || preg_match('/^SHEET\d+$/', $u)) {
            return null;
        }
        if (str_contains($u, 'SISA DEPOSIT')) {
            return ['type' => 'DEPOSIT_SISA', 'confidence' => 95, 'is_summary' => true];
        }
        if (str_contains($u, 'UANG RITEL')) {
            return ['type' => 'DEPOSIT_RETAIL_CASH', 'confidence' => 95, 'is_summary' => true];
        }
        if (str_contains($u, 'REKAP BFJ') || str_contains($u, 'REKAP MAA')) {
            return ['type' => 'SALES_RECAP_MATRIX', 'confidence' => 95, 'is_summary' => true];
        }
        if (str_contains($u, 'REKAP RITEL') || str_contains($u, 'REKAP DEPOSIT')) {
            return ['type' => 'SALES_RETAIL_MATRIX', 'confidence' => 95, 'is_summary' => true];
        }
        if (str_starts_with($u, 'MUAT BAWAH')) {
            return ['type' => 'LOADING_LOG', 'confidence' => 95, 'is_summary' => false];
        }
        if (str_starts_with($u, 'STOCK PAK') || str_starts_with($u, 'STOK PAK')) {
            return ['type' => 'CUSTOMER_STOCK', 'confidence' => 95, 'is_summary' => false];
        }
        if ($u === 'SECURITY') {
            return ['type' => 'PAYROLL_SECURITY', 'confidence' => 95, 'is_summary' => true];
        }
        if ($u === 'REKAP' || str_starts_with($u, 'COPY OF REKAP')) {
            return ['type' => 'PAYROLL_RECAP', 'confidence' => 95, 'is_summary' => true];
        }
        if (str_contains($u, 'ARUS KAS') && str_contains($u, 'REKAP') || $u === 'LAPORAN REKAP') {
            return ['type' => 'FINANCE_DETAIL', 'confidence' => 90, 'is_summary' => false];
        }
        if (str_starts_with($u, 'LAPORAN ALL') || $u === 'ALL') {
            return ['type' => 'FINANCE_STATEMENT', 'confidence' => 60, 'is_summary' => true];
        }

        return null;
    }

    public const SUMMARY_HINTS = ['REKAP', 'RINGKAS', 'SUMMARY', 'TOTAL', 'SISA', 'REPORT', 'LAPORAN', 'REKAPITULASI', 'SLIP GAJI', 'COPY OF'];

    /** @return array{type:string,confidence:int,is_summary:bool} */
    public static function classifyFile(string $filename, array $sheetNames): array
    {
        $u = mb_strtoupper($filename);
        $map = [
            'LAPORAN KEUANGAN' => 'FINANCE', 'KEUANGAN' => 'FINANCE', 'KEUANGA' => 'FINANCE', 'ARUS KAS' => 'FINANCE',
            'PENJUALAN' => 'SALES', 'SALES' => 'SALES',
            'DEPOSIT' => 'CUSTOMER_DEPOSIT',
            'GAJI' => 'PAYROLL', 'PAYROLL' => 'PAYROLL',
            'SURAT' => 'CORRESPONDENCE', 'NOMOR SURAT' => 'CORRESPONDENCE', 'INVOICE' => 'INVOICE_REGISTER',
            'KWITANSI' => 'RECEIPT_REGISTER',
            'SPAREPART' => 'SPAREPART_MASTER', 'GUDANG' => 'SPAREPART_MASTER',
            'STOK' => 'STOCK_CARD', 'KARTU STOK' => 'STOCK_CARD', 'OPNAME' => 'STOCK_OPNAME',
        ];
        foreach ($map as $hint => $type) {
            if (str_contains($u, $hint)) {
                return ['type' => $type, 'confidence' => 85, 'is_summary' => false];
            }
        }
        // fall back to sheet majority vote
        $votes = [];
        foreach ($sheetNames as $s) {
            $c = self::classifySheet((string) $s, []);
            $votes[$c['type']] = ($votes[$c['type']] ?? 0) + 1;
        }
        if ($votes !== []) {
            arsort($votes);
            $top = array_key_first($votes);

            return ['type' => $top, 'confidence' => 55, 'is_summary' => false];
        }

        return ['type' => 'SALES', 'confidence' => 20, 'is_summary' => false];
    }

    /** @param  string[]  $headers */
    public static function classifySheet(string $sheetName, array $headers): array
    {
        $u = mb_strtoupper(trim($sheetName));
        foreach (self::SUMMARY_HINTS as $hint) {
            if (str_contains($u, $hint)) {
                $type = str_contains($u, 'DEPOSIT') || str_contains($u, 'SISA') ? 'CUSTOMER_DEPOSIT'
                    : (str_contains($u, 'RITEL') || str_contains($u, 'BFJ') || str_contains($u, 'MAA') ? 'SALES'
                    : (str_contains($u, 'STOK') || str_contains($u, 'STOCK') ? 'STOCK_REPORT' : 'FINANCE'));

                return ['type' => $type, 'confidence' => 80, 'is_summary' => true];
            }
        }
        // daily sales sheets "1 September 2026"
        if (preg_match('/^\d{1,2}\s+(JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|DESEMBER|JAN|FEB|MAR|APR|JUN|JUL|AGU|SEP|OKT|NOV|DES)/i', $u)) {
            return ['type' => 'SALES', 'confidence' => 90, 'is_summary' => false];
        }
        $h = mb_strtoupper(implode(' | ', $headers));
        $score = [
            'SALES' => substr_count($h, 'SOPIR') + substr_count($h, 'POLIS') + substr_count($h, 'KUBIKASI') + substr_count($h, 'MATERIAL') + substr_count($h, 'RITEL') + substr_count($h, 'REKENING'),
            'CUSTOMER_DEPOSIT' => substr_count($h, 'DEPOSIT') * 2 + substr_count($h, 'NO DO'),
            'PAYROLL' => substr_count($h, 'LEMBUR') * 2 + substr_count($h, 'JAM NORMAL') + substr_count($h, 'STAND') + substr_count($h, 'KEGIATAN'),
            'SPAREPART_MASTER' => substr_count($h, 'SPAREPART') + substr_count($h, 'KODE') + substr_count($h, 'RAK'),
            'SPAREPART_ISSUE' => substr_count($h, 'QTY KELUAR') * 2 + substr_count($h, 'PEMAKAI') + substr_count($h, 'KEPERLUAN'),
            'SPAREPART_OPENING' => substr_count($h, 'QTY MASUK') * 2 + substr_count($h, 'SUPPLIER') + substr_count($h, 'PENERIMA'),
            'STOCK_CARD' => substr_count($h, 'KARTU') + substr_count($h, 'SALDO') + substr_count($h, 'MASUK') + substr_count($h, 'KELUAR'),
            'STOCK_OPNAME' => substr_count($h, 'FISIK') * 2 + substr_count($h, 'SELISIH') + substr_count($h, 'SISTEM'),
            'INVOICE_REGISTER' => substr_count($h, 'INVOICE') * 2 + substr_count($h, 'KUBIKASI'),
            'RECEIPT_REGISTER' => substr_count($h, 'KWITANSI') * 2 + substr_count($h, 'NOMINAL'),
            'CORRESPONDENCE' => substr_count($h, 'NOMOR SURAT') * 2 + substr_count($h, 'PERIHAL'),
            'FINANCE' => substr_count($h, 'KAS') + substr_count($h, 'SALDO') + substr_count($h, 'MASUK') + substr_count($h, 'KELUAR'),
        ];
        arsort($score);
        $top = (string) array_key_first($score);
        $conf = min(95, 40 + ($score[$top] * 12));

        return ['type' => $score[$top] > 0 ? $top : 'SALES', 'confidence' => $score[$top] > 0 ? $conf : 25, 'is_summary' => false];
    }
}
