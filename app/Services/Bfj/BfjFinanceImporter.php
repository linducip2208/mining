<?php

namespace App\Services\Bfj;

/**
 * Finance workbook parser (§24-§30): RAW STAGING → CLASSIFICATION →
 * ACCOUNT MAPPING → REVIEW → OPTIONAL POSTING. Internal transfers never
 * become income/expense (§28).
 */
final class BfjFinanceImporter
{
    public const FLOW_TYPES = ['OPERATING', 'INVESTING', 'FINANCING', 'TRANSFER_INTERNAL', 'CLEARING', 'OPENING_BALANCE'];

    /** @return array{normalized:array<string,mixed>,issues:array} */
    public static function normalize(array $row): array
    {
        $issues = [];
        $desc = BfjNormalizer::squeeze((string) ($row['KETERANGAN'] ?? $row['DESCRIPTION'] ?? $row['URAIAN'] ?? ''));
        $in = BfjParsers::parseMoney($row['MASUK'] ?? $row['DEBET'] ?? $row['INFLOW'] ?? $row['PEMASUKAN'] ?? 0);
        $out = BfjParsers::parseMoney($row['KELUAR'] ?? $row['KREDIT'] ?? $row['OUTFLOW'] ?? $row['PENGELUARAN'] ?? 0);
        $date = BfjParsers::parseDate($row['TANGGAL'] ?? $row['DATE'] ?? null);
        if ($date['error']) {
            $issues[] = ['code' => $date['error'], 'severity' => 'ERROR', 'message' => 'Tanggal keuangan invalid'];
        }
        $u = BfjNormalizer::upper($desc);
        $flow = 'OPERATING';
        if (str_contains($u, 'TRANSFER INTERNAL') || (str_contains($u, 'TRANSFER') && str_contains($u, 'ANTAR'))) {
            $flow = 'TRANSFER_INTERNAL';
        } elseif (str_contains($u, 'SALDO AWAL') || str_contains($u, 'BAWAAN') || str_contains($u, 'CARRY')) {
            $flow = 'OPENING_BALANCE';
        } elseif (str_contains($u, 'ALASEN') || str_contains($u, 'PRIBADI') || str_contains($u, 'TALANGAN')) {
            $flow = 'CLEARING';
        } elseif (str_contains($u, 'MODAL') || str_contains($u, 'PINJAMAN') || str_contains($u, 'LOAN')) {
            $flow = 'FINANCING';
        } elseif (str_contains($u, 'ALAT') || str_contains($u, 'ASET') || str_contains($u, 'INVESTASI')) {
            $flow = 'INVESTING';
        }

        return ['normalized' => [
            'date' => $date['value'],
            'description' => $desc,
            'inflow' => $in['value'] ?? 0,
            'outflow' => $out['value'] ?? 0,
            'net' => ($in['value'] ?? 0) - ($out['value'] ?? 0),
            'flow_type' => $flow,
            'account_hint' => BfjNormalizer::squeeze((string) ($row['REKENING'] ?? $row['AKUN'] ?? $row['ACCOUNT'] ?? '')),
        ], 'issues' => $issues];
    }
}
