<?php

namespace App\Services\Bfj;

/**
 * Deterministic fingerprints for idempotent re-import (§73, §117).
 */
final class BfjFingerprinter
{
    public static function file(string $path): string
    {
        return hash_file('sha256', $path) ?: '';
    }

    /** @param  array<string,mixed>  $parts */
    public static function row(array $parts): string
    {
        ksort($parts);
        $canon = implode('|', array_map(
            fn ($k, $v) => $k.'='.BfjNormalizer::upper((string) ($v ?? '')),
            array_keys($parts),
            array_values($parts)
        ));

        return hash('sha256', $canon);
    }

    public static function sales(array $n): string
    {
        return self::row([
            'date' => $n['transaction_date'] ?? '', 'do' => $n['legacy_do_number'] ?? '',
            'cust' => $n['customer'] ?? '', 'veh' => $n['vehicle'] ?? '',
            'mat' => $n['material'] ?? '', 'vol' => $n['volume_m3'] ?? '', 'amt' => $n['gross_amount'] ?? '',
        ]);
    }

    public static function deposit(array $n): string
    {
        return self::row([
            'cust' => $n['customer'] ?? '', 'date' => $n['date'] ?? '', 'ref' => $n['reference'] ?? '',
            'amt' => $n['amount'] ?? '', 'mat' => $n['material'] ?? '',
        ]);
    }

    public static function sparepart(array $n): string
    {
        return self::row([
            'date' => $n['date'] ?? '', 'item' => $n['code'] ?? '', 'qty' => $n['qty'] ?? '',
            'type' => $n['movement'] ?? '', 'equip' => $n['equipment'] ?? '',
        ]);
    }

    public static function payroll(array $n): string
    {
        return self::row([
            'emp' => $n['employee'] ?? '', 'date' => $n['date'] ?? '',
            'hours' => $n['normal_hours'] ?? '', 'comp' => $n['component'] ?? '',
        ]);
    }
}
