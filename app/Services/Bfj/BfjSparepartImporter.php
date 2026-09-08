<?php

namespace App\Services\Bfj;

/**
 * Sparepart parsers (§53-§63): master, opening (STOK LAMA), issue,
 * stock card/opname/report as reconciliation benchmarks.
 */
final class BfjSparepartImporter
{
    /** @return array{normalized:array<string,mixed>,issues:array} */
    public static function normalizeMaster(array $row): array
    {
        $issues = [];
        $code = BfjNormalizer::upper((string) ($row['KODE'] ?? $row['KODE SPAREPART'] ?? ''));
        if ($code === '') {
            $issues[] = ['code' => 'MISSING_FIELD', 'severity' => 'ERROR', 'message' => 'Kode sparepart kosong'];
        }

        return ['normalized' => [
            'code' => $code,
            'name' => BfjNormalizer::squeeze((string) ($row['NAMA SPAREPART'] ?? $row['NAMA'] ?? '')),
            'category' => BfjNormalizer::upper((string) ($row['KATEGORI'] ?? '')),
            'unit' => BfjNormalizer::upper((string) ($row['SATUAN'] ?? '')),
            'location' => BfjNormalizer::normalizeLocation((string) ($row['LOKASI'] ?? $row['RAK'] ?? '')),
            'location_legacy' => BfjNormalizer::squeeze((string) ($row['LOKASI'] ?? '')),
        ], 'issues' => $issues];
    }

    /** @return array{normalized:array<string,mixed>,issues:array} */
    public static function normalizeMovement(array $row, string $movement): array
    {
        $issues = [];
        $qtyRaw = $row['QTY MASUK'] ?? $row['QTY KELUAR'] ?? $row['QTY'] ?? null;
        $qty = BfjParsers::parseQty($qtyRaw);
        if ($qty['error'] === 'ERROR_MISSING_QTY') {
            $issues[] = ['code' => 'ERROR_MISSING_QTY', 'severity' => 'ERROR', 'message' => 'Qty kosong — tidak diasumsikan 0'];
        }
        $date = BfjParsers::parseDate($row['TANGGAL'] ?? null);
        if ($date['error']) {
            $issues[] = ['code' => $date['error'], 'severity' => 'ERROR', 'message' => 'Tanggal movement invalid'];
        }
        $ket = BfjNormalizer::upper((string) ($row['KETERANGAN'] ?? ''));
        $isOpening = str_contains($ket, 'STOK LAMA');
        $normalized = [
            'date' => $date['value'],
            'code' => BfjNormalizer::upper((string) ($row['KODE'] ?? $row['KODE SPAREPART'] ?? '')),
            'qty' => $qty['value'],
            'movement' => $isOpening ? 'OPENING_BALANCE' : $movement,
            'is_opening' => $isOpening,
            'supplier' => BfjNormalizer::squeeze((string) ($row['SUPPLIER'] ?? '')),
            'equipment' => BfjNormalizer::upper((string) ($row['UNIT/ALAT'] ?? $row['UNIT'] ?? '')),
            'user' => BfjNormalizer::squeeze((string) ($row['PEMAKAI'] ?? $row['PENERIMA'] ?? '')),
            'purpose' => BfjNormalizer::squeeze((string) ($row['KEPERLUAN'] ?? $row['KETERANGAN'] ?? '')),
            'condition' => BfjNormalizer::squeeze((string) ($row['KONDISI'] ?? '')),
            'note_flags' => BfjNormalizer::normalizeSparepartNote((string) ($row['KETERANGAN'] ?? '')),
        ];
        $normalized['fingerprint'] = BfjFingerprinter::sparepart($normalized);

        return ['normalized' => $normalized, 'issues' => $issues];
    }
}
