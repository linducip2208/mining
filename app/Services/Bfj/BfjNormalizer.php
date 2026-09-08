<?php

namespace App\Services\Bfj;

/**
 * Canonical normalizers for BFJ legacy values (§4-§8, §44-§51, §53-§54, §63).
 * Pure functions: source_value → normalized_value, never silently merge.
 */
final class BfjNormalizer
{
    public const COMPANY_ALIASES = ['BFJ', 'PT BFJ', 'PT. BFJ', 'PT BARUS FAMILLY JAYA', 'BARUS FAMILLY JAYA', 'PT. BARUS FAMILLY JAYA'];

    /** @var array<string,string> canonical customer alias → canonical name */
    public const CUSTOMER_ALIASES = [
        'PT SMJ' => 'PT SINAR MUSI JAYA',
        'SINAR MUSI JAYA' => 'PT SINAR MUSI JAYA',
        'PT SINAR MUSI JAYA' => 'PT SINAR MUSI JAYA',
    ];

    public const MATERIAL_ALIASES = [
        'ABU BATU' => 'ABU BATU', 'SPLIT 1/1' => 'SPLIT 1/1', 'SPLIT 1/2' => 'SPLIT 1/2',
        'SPLIT 2/3' => 'SPLIT 2/3', 'SPLIT 3/5' => 'SPLIT 3/5', 'SPLIT 5/7' => 'SPLIT 5/7',
        'AGREGAT A' => 'AGREGAT A', 'AGREGAT B' => 'AGREGAT B', 'BATU BELAH' => 'BATU BELAH',
        'BLASTING' => 'BLASTING', 'QUARRY WES' => 'QUARRY WES',
    ];

    public static function squeeze(string $v): string
    {
        return trim(preg_replace('/\s+/', ' ', $v) ?? '');
    }

    public static function upper(string $v): string
    {
        return mb_strtoupper(self::squeeze($v));
    }

    public static function normalizeCompany(string $v): string
    {
        $u = self::upper($v);

        return in_array($u, array_map(fn ($a) => self::upper($a), self::COMPANY_ALIASES), true) ? 'BFJ' : $u;
    }

    public static function normalizeCustomer(string $v): string
    {
        $u = self::upper($v);

        return self::CUSTOMER_ALIASES[$u] ?? $u;
    }

    /** BG8506DS / bg 8506 ds → BG 8506 DS, keeps legacy original separately. */
    public static function normalizePlate(string $v): string
    {
        $u = self::upper(preg_replace('/[^A-Z0-9]/i', '', $v) ?? '');
        if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/', $u, $m)) {
            return $m[1].' '.$m[2].' '.$m[3];
        }

        return self::upper($v);
    }

    public static function normalizeMaterial(string $v): string
    {
        $u = self::upper($v);

        return self::MATERIAL_ALIASES[$u] ?? $u;
    }

    public static function normalizeInvoiceStatus(string $v): string
    {
        $u = self::upper($v);
        if (in_array($u, ['LUNAS', 'PAID', 'LUNAS ✅', 'SUDAH LUNAS'], true)) {
            return 'PAID';
        }
        if (in_array($u, ['BELUM LUNAS', 'BELUM LUNAS ❌', 'OUTSTANDING', 'UNPAID'], true)) {
            return 'OUTSTANDING';
        }

        return $u;
    }

    public static function normalizePaymentMethod(string $v): string
    {
        $u = self::upper($v);
        if (str_contains($u, 'TRANSFER')) {
            return 'TRANSFER';
        }
        if (str_contains($u, 'CASH') || str_contains($u, 'TUNAI') || str_contains($u, 'RITEL')) {
            return 'CASH';
        }
        if (str_contains($u, 'GIRO')) {
            return 'GIRO';
        }
        if (str_contains($u, 'DEPOSIT')) {
            return 'DEPOSIT';
        }

        return $u;
    }

    /** DINDING is a valid flat storage location (§53-54). */
    public static function normalizeLocation(string $v): string
    {
        return self::upper($v);
    }

    /** @return array{condition:string|null,inventory:string|null,procurement:string|null,recon:string|null} */
    public static function normalizeSparepartNote(string $v): array
    {
        $u = self::upper($v);
        $out = ['condition' => null, 'inventory' => null, 'procurement' => null, 'recon' => null];
        if (str_contains($u, 'BAIK') || str_contains($u, 'BARU')) {
            $out['condition'] = 'BAIK';
        }
        if (str_contains($u, 'RUSAK')) {
            $out['condition'] = 'RUSAK';
        }
        if (str_contains($u, 'HABIS')) {
            $out['inventory'] = 'HABIS';
        }
        if (str_contains($u, 'DIPESAN') || str_contains($u, 'SEDANG DIPESAN')) {
            $out['procurement'] = 'DIPESAN';
        }
        if (str_contains($u, 'SELISIH')) {
            $out['recon'] = 'SELISIH';
        }

        return $out;
    }

    /**
     * Flexible legacy letter-number tokenizer (§44-45).
     *
     * @return array{seq:string,doc_type:string|null,division:string|null,company:string|null,counterparty:string|null,month_roman:string|null,year:string|null}|null
     */
    public static function parseLetterNumber(string $v): ?array
    {
        $s = self::squeeze($v);
        $parts = explode('/', $s);
        if (count($parts) < 3) {
            return null;
        }
        $seq = trim($parts[0]);
        if (! ctype_digit($seq)) {
            return null;
        }
        $year = trim(end($parts));
        $roman = trim(prev($parts) ?: '');
        $roman = preg_match('/^(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)$/i', $roman) ? strtoupper($roman) : null;
        $middle = array_slice($parts, 1, $roman ? -2 : null);
        $docType = null;
        $division = null;
        $company = null;
        $counterparty = null;
        $knownDoc = ['SP', 'SK', 'INT', 'BA', 'PNG', 'BAST', 'PO', 'HRD', 'ADM', 'FIN', 'OPS', 'MKT'];
        foreach ($middle as $m) {
            $m = trim($m);
            if (str_contains($m, '-')) {
                [$a, $b] = array_map('trim', explode('-', $m, 2));
                if (in_array(strtoupper($a), $knownDoc, true)) {
                    $docType ??= strtoupper($a);
                } else {
                    $division ??= strtoupper($a);
                }
                if (str_contains(strtoupper($b), 'BFJ')) {
                    $company ??= 'BFJ';
                } else {
                    $counterparty ??= strtoupper($b);
                }
            } elseif (in_array(strtoupper($m), ['SP', 'SK', 'INT', 'BA', 'PNG', 'BAST', 'PO'], true)) {
                $docType ??= strtoupper($m);
            } elseif (in_array(strtoupper($m), ['HRD', 'ADM', 'FIN', 'OPS', 'MKT'], true)) {
                $division ??= strtoupper($m);
            } elseif (str_contains(strtoupper($m), 'BFJ')) {
                $company ??= 'BFJ';
            } else {
                $division ??= strtoupper($m);
            }
        }

        return ['seq' => $seq, 'doc_type' => $docType, 'division' => $division, 'company' => $company, 'counterparty' => $counterparty, 'month_roman' => $roman, 'year' => ctype_digit($year) ? $year : null];
    }

    public static function paymentChannel(string $column): string
    {
        $u = self::upper($column);
        if (str_contains($u, 'ALASEN') || str_contains($u, 'PERSONAL') || str_contains($u, 'PRIBADI') || str_contains($u, 'TALANGAN')) {
            return 'PERSONAL_CLEARING';
        }
        if (str_contains($u, 'PERUSAHAAN')) {
            return 'COMPANY_BANK';
        }

        return 'CASH';
    }
}
