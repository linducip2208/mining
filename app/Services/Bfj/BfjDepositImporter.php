<?php

namespace App\Services\Bfj;

/**
 * Deposit workbook parser (§17-§23): SISA DEPOSIT stays benchmark,
 * customer sheets → DEPOSIT/CONSUMPTION/ADJUSTMENT/REFUND/OPENING_BALANCE.
 */
final class BfjDepositImporter
{
    /** @return array{normalized:array<string,mixed>,issues:array} */
    public static function normalize(array $row): array
    {
        $issues = [];
        $get = fn (string ...$keys) => array_find($keys, fn ($k) => isset($row[$k]) && $row[$k] !== '') ? $row[array_find($keys, fn ($k) => isset($row[$k]) && $row[$k] !== '')] : null;
        $date = BfjParsers::parseDate($row['TANGGAL'] ?? $row['DATE'] ?? null);
        $penjualan = BfjParsers::parseMoney($row['PENJUALAN'] ?? null);
        $deposit = BfjParsers::parseMoney($row['DEPOSIT'] ?? null);
        if ($date['error']) {
            $issues[] = ['code' => $date['error'], 'severity' => 'ERROR', 'message' => 'Tanggal deposit invalid'];
        }
        $type = 'DEPOSIT';
        if (($penjualan['value'] ?? 0) > 0) {
            $type = 'CONSUMPTION';
        }
        if (str_contains(BfjNormalizer::upper((string) ($row['KET'] ?? $row['KETERANGAN'] ?? '')), 'REFUND')) {
            $type = 'REFUND';
        }
        $amount = $type === 'CONSUMPTION' ? ($penjualan['value'] ?? 0) : ($deposit['value'] ?? 0);
        $normalized = [
            'date' => $date['value'],
            'legacy_do_number' => BfjNormalizer::squeeze((string) ($row['NO DO'] ?? '')),
            'driver' => BfjNormalizer::squeeze((string) ($row['NAMA SOPIR'] ?? '')),
            'vehicle' => isset($row['NO POL']) && $row['NO POL'] !== '' ? BfjNormalizer::normalizePlate((string) $row['NO POL']) : null,
            'material' => isset($row['MATERIAL']) && $row['MATERIAL'] !== '' ? BfjNormalizer::normalizeMaterial((string) $row['MATERIAL']) : null,
            'type' => $type,
            'amount' => $amount,
            'volume' => BfjParsers::parseQty($row['VOLUME'] ?? $row['KUBIKASI'] ?? null)['value'],
            'legacy_invoice' => BfjNormalizer::squeeze((string) ($row['INVOICE'] ?? $row['INV NO'] ?? '')),
            'notes' => BfjNormalizer::squeeze((string) ($row['KET'] ?? $row['KETERANGAN'] ?? '')),
            'customer' => isset($row['CUSTOMER']) ? BfjNormalizer::normalizeCustomer((string) $row['CUSTOMER']) : null,
        ];
        $normalized['fingerprint'] = BfjFingerprinter::deposit([
            'customer' => $normalized['customer'] ?? '', 'date' => $normalized['date'],
            'reference' => $normalized['legacy_do_number'], 'amount' => $amount, 'material' => $normalized['material'] ?? '',
            'driver' => $normalized['driver'] ?? '', 'vehicle' => $normalized['vehicle'] ?? '', 'type' => $type,
        ]);

        return ['normalized' => $normalized, 'issues' => $issues];
    }
}
