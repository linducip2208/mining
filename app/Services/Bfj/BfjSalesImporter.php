<?php

namespace App\Services\Bfj;

use App\Support\SpreadsheetReader;

/**
 * Daily sales workbook parser (§9-§16): transactional sheets → canonical
 * staging; REKAP sheets stay RECONCILE_ONLY (§64).
 */
final class BfjSalesImporter
{
    public const HEADERS = [
        'NO DO' => 'legacy_do_number', 'NOMOR DO' => 'legacy_do_number',
        'NAMA SOPIR' => 'driver', 'SOPIR' => 'driver', 'DRIVER' => 'driver',
        'NO POLIS' => 'vehicle', 'NO POL' => 'vehicle', 'NOPOL' => 'vehicle', 'PLAT' => 'vehicle',
        'CUSTOMER' => 'customer', 'COSTUMER' => 'customer', 'PELANGGAN' => 'customer', 'NAMA CUSTOMER' => 'customer',
        'JENIS MATERIAL' => 'material', 'MATERIAL' => 'material', 'PRODUK' => 'material',
        'KUBIKASI TERJUAL' => 'volume_m3', 'KUBIKASI' => 'volume_m3', 'VOLUME' => 'volume_m3', 'QTY' => 'volume_m3',
        'HARGA' => 'unit_price', 'HARGA SATUAN' => 'unit_price',
        'RITEL' => 'ritel_amount', 'REKENING ALASEN' => 'alasen_amount', 'REKENING PERUSAHAAN' => 'perusahaan_amount',
        'KET' => 'notes', 'KETERANGAN' => 'notes', 'KETERANGAN MATERIAL' => 'material_notes',
    ];

    /** @return array{normalized:array<string,mixed>,issues:array<int,array{code:string,severity:string,message:string}>} */
    public static function normalize(array $row, float $tolerance = 1.0): array
    {
        $issues = [];
        $get = fn (string $f) => $row[$f] ?? $row[mb_strtoupper($f)] ?? null;
        $date = BfjParsers::parseDate($get('TANGGAL') ?? $get('DATE') ?? null);
        $vol = BfjParsers::parseQty($get('KUBIKASI TERJUAL') ?? $get('KUBIKASI') ?? $get('VOLUME') ?? null);
        $price = BfjParsers::parseMoney($get('HARGA') ?? null);
        $ritel = BfjParsers::parseMoney($get('RITEL') ?? null);
        $alasen = BfjParsers::parseMoney($get('REKENING ALASEN') ?? null);
        $perusahaan = BfjParsers::parseMoney($get('REKENING PERUSAHAAN') ?? null);

        if ($date['error']) {
            $issues[] = ['code' => $date['error'] === 'FORMULA_ERROR' ? 'FORMULA_ERROR' : 'INVALID_DATE', 'severity' => 'ERROR', 'message' => 'Tanggal tidak valid: '.($get('TANGGAL') ?? '')];
        }
        if (($vol['error'] ?? null) === 'ERROR_MISSING_QTY') {
            $issues[] = ['code' => 'ERROR_MISSING_QTY', 'severity' => 'ERROR', 'message' => 'Kubikasi kosong'];
        }
        $gross = ($ritel['value'] ?? 0) + ($alasen['value'] ?? 0) + ($perusahaan['value'] ?? 0);
        $expected = ($vol['value'] ?? 0) * ($price['value'] ?? 0);
        $status = 'MATCH';
        if ($expected > 0 && abs($gross - $expected) > $tolerance) {
            $status = abs($gross - $expected) < max(1000, $expected * 0.005) ? 'ROUNDING_VARIANCE' : 'FORMULA_VARIANCE';
            $issues[] = ['code' => 'AMOUNT_VARIANCE', 'severity' => 'WARNING', 'message' => "Volume×harga {$expected} vs tercatat {$gross} ({$status})"];
        }

        $channel = 'CASH';
        if (($alasen['value'] ?? 0) > 0) {
            $channel = 'PERSONAL_CLEARING';
        } elseif (($perusahaan['value'] ?? 0) > 0) {
            $channel = 'COMPANY_BANK';
        }
        $driver = BfjNormalizer::squeeze((string) ($get('NAMA SOPIR') ?? $get('SOPIR') ?? ''));
        $vehicleRaw = (string) ($get('NO POLIS') ?? $get('NO POL') ?? '');
        $customerRaw = (string) ($get('CUSTOMER') ?? $get('COSTUMER') ?? '');
        $materialRaw = (string) ($get('JENIS MATERIAL') ?? $get('MATERIAL') ?? '');

        $normalized = [
            'transaction_date' => $date['value'],
            'legacy_do_number' => BfjNormalizer::squeeze((string) ($get('NO DO') ?? '')),
            'driver' => $driver,
            'vehicle' => $vehicleRaw ? BfjNormalizer::normalizePlate($vehicleRaw) : null,
            'vehicle_legacy' => $vehicleRaw,
            'customer' => $customerRaw ? BfjNormalizer::normalizeCustomer($customerRaw) : null,
            'customer_legacy' => $customerRaw,
            'material' => $materialRaw ? BfjNormalizer::normalizeMaterial($materialRaw) : null,
            'material_legacy' => $materialRaw,
            'volume_m3' => $vol['value'],
            'unit_price' => $price['value'],
            'gross_amount' => $gross,
            'expected_amount' => $expected,
            'amount_status' => $status,
            'payment_channel' => $channel,
            'ritel_amount' => $ritel['value'] ?? 0,
            'alasen_amount' => $alasen['value'] ?? 0,
            'perusahaan_amount' => $perusahaan['value'] ?? 0,
            'notes' => BfjNormalizer::squeeze((string) ($get('KET') ?? $get('KETERANGAN') ?? '')),
            'fingerprint' => BfjFingerprinter::sales([
                'transaction_date' => $date['value'], 'legacy_do_number' => $get('NO DO'),
                'customer' => $customerRaw, 'vehicle' => $vehicleRaw,
                'material' => $materialRaw, 'volume_m3' => $vol['value'], 'gross_amount' => $gross,
            ]),
        ];

        return ['normalized' => $normalized, 'issues' => $issues];
    }

    public static function readSheet(string $path, string $sheet): array
    {
        return SpreadsheetReader::read($path, $sheet);
    }
}
