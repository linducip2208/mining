<?php

namespace App\Services\Bfj;

/**
 * Document parsers (§44-§52): flexible letter tokens, invoice status,
 * receipt history. Legacy numbers preserved; sequence never advanced
 * unless explicitly selected (§47).
 */
final class BfjDocumentImporter
{
    /** @return array{normalized:array<string,mixed>,issues:array} */
    public static function normalizeLetter(array $row): array
    {
        $issues = [];
        $number = BfjNormalizer::squeeze((string) ($row['NOMOR SURAT'] ?? $row['NOMOR'] ?? $row['NO'] ?? ''));
        if (in_array(mb_strtoupper($number), ['#ERROR!', '#VALUE!', '#N/A', 'ERROR'], true)) {
            $issues[] = ['code' => 'FORMULA_ERROR', 'severity' => 'ERROR', 'message' => "Nomor surat berisi formula error: {$number}"];
        }
        $parsed = $number !== '' ? BfjNormalizer::parseLetterNumber($number) : null;
        if ($number !== '' && ! $parsed && ! BfjRealCommon::any($issues, fn ($i) => $i['code'] === 'FORMULA_ERROR')) {
            $issues[] = ['code' => 'NUMBER_PATTERN_VARIANCE', 'severity' => 'WARNING', 'message' => "Nomor tidak berpola standar: {$number}"];
        }
        $date = BfjParsers::parseDate($row['TANGGAL'] ?? null);
        if ($date['error']) {
            $issues[] = ['code' => $date['error'], 'severity' => $date['error'] === 'MISSING_FIELD' ? 'WARNING' : 'ERROR', 'message' => 'Tanggal surat invalid'];
        }

        return ['normalized' => [
            'number' => $number, 'legacy_number' => true, 'tokens' => $parsed,
            'date' => $date['value'], 'subject' => BfjNormalizer::squeeze((string) ($row['PERIHAL'] ?? '')),
            'recipient' => BfjNormalizer::squeeze((string) ($row['TUJUAN'] ?? '')),
        ], 'issues' => $issues];
    }

    /** @return array{normalized:array<string,mixed>,issues:array} */
    public static function normalizeInvoice(array $row): array
    {
        $issues = [];
        $rawStatus = (string) ($row['STATUS'] ?? '');
        $date = BfjParsers::parseDate($row['TANGGAL'] ?? null);
        $total = BfjParsers::parseMoney($row['TOTAL'] ?? null);
        if (! $total['value']) {
            $issues[] = ['code' => 'MISSING_FIELD', 'severity' => 'ERROR', 'message' => 'Total invoice kosong'];
        }
        if ($date['error']) {
            $issues[] = ['code' => $date['error'], 'severity' => 'ERROR', 'message' => 'Tanggal invoice invalid'];
        }

        return ['normalized' => [
            'number' => BfjNormalizer::squeeze((string) ($row['NOMOR INVOICE'] ?? $row['NOMOR'] ?? '')),
            'date' => $date['value'],
            'customer' => isset($row['NAMA CUSTOMER']) ? BfjNormalizer::normalizeCustomer((string) $row['NAMA CUSTOMER']) : (isset($row['CUSTOMER']) ? BfjNormalizer::normalizeCustomer((string) $row['CUSTOMER']) : null),
            'product' => isset($row['PRODUK']) ? BfjNormalizer::normalizeMaterial((string) $row['PRODUK']) : null,
            'volume' => BfjParsers::parseQty($row['KUBIKASI'] ?? null)['value'],
            'total' => $total['value'],
            'status_raw' => $rawStatus,
            'status' => BfjNormalizer::normalizeInvoiceStatus($rawStatus),
            'notes' => BfjNormalizer::squeeze((string) ($row['KETERANGAN'] ?? '')),
        ], 'issues' => $issues];
    }

    /** @return array{normalized:array<string,mixed>,issues:array} */
    public static function normalizeReceipt(array $row): array
    {
        $issues = [];
        $amount = BfjParsers::parseMoney($row['NOMINAL'] ?? $row['AMOUNT'] ?? null);
        $date = BfjParsers::parseDate($row['TANGGAL'] ?? null);
        if (! $amount['value']) {
            $issues[] = ['code' => 'MISSING_FIELD', 'severity' => 'ERROR', 'message' => 'Nominal kwitansi kosong'];
        }

        return ['normalized' => [
            'number' => BfjNormalizer::squeeze((string) ($row['NOMOR KWITANSI'] ?? $row['NOMOR'] ?? '')),
            'date' => $date['value'] ?? ($date['error'] ? null : null),
            'customer' => isset($row['NAMA CUSTOMER']) ? BfjNormalizer::normalizeCustomer((string) $row['NAMA CUSTOMER']) : null,
            'invoice_ref' => BfjNormalizer::squeeze((string) ($row['TERKAIT INVOICE'] ?? $row['INVOICE'] ?? '')),
            'amount' => $amount['value'],
            'method' => BfjNormalizer::normalizePaymentMethod((string) ($row['METODE BAYAR'] ?? $row['METODE'] ?? '')),
            'notes' => BfjNormalizer::squeeze((string) ($row['KETERANGAN'] ?? '')),
            'date_error' => $date['error'],
        ], 'issues' => $date['error'] ? [['code' => $date['error'], 'severity' => 'ERROR', 'message' => 'Tanggal kwitansi invalid']] : $issues];
    }
}
