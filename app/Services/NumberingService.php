<?php

namespace App\Services;

use App\Models\DocumentNumbering;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    /**
     * Generate next document number, concurrency-safe (row lock + period reset).
     * Format placeholders: {PREFIX} {Y} {M} {YM} {YMD} {SEQ} {SEQ:N}
     * Context placeholders: {TYPE} {DEPARTMENT} {COMPANY} {SITE} {MONTH_ROMAN} {DAY}
     *
     * @param  array<string,string>  $context
     */
    public static function generate(string $docType, ?int $companyId = null, ?int $siteId = null, array $context = []): string
    {
        return DB::transaction(function () use ($docType, $companyId, $siteId, $context) {
            $cfg = DocumentNumbering::where('doc_type', $docType)
                ->where(function ($q) use ($companyId) {
                    $q->whereNull('company_id')->orWhere('company_id', $companyId);
                })
                ->where(function ($q) use ($siteId) {
                    $q->whereNull('site_id')->orWhere('site_id', $siteId);
                })
                ->orderByRaw('company_id IS NULL, site_id IS NULL')
                ->lockForUpdate()
                ->first();

            if (! $cfg) {
                $suffix = self::settingSuffix($docType);
                $cfg = DocumentNumbering::create([
                    'company_id' => null,
                    'site_id' => null,
                    'doc_type' => $docType,
                    'format' => Setting::get('numbering.'.$suffix.'_format', '{PREFIX}-{YM}--{SEQ}'),
                    'current_seq' => 0,
                    'padding' => self::paddingFromFormat((string) Setting::get('numbering.'.$suffix.'_format', '{PREFIX}-{YM}--{SEQ}')),
                    'reset_period' => 'MONTHLY',
                ]);
            } elseif (is_null($cfg->company_id) && is_null($cfg->site_id)) {
                // Keep the locked sequence intact while allowing the global UI format to take effect.
                $configuredFormat = Setting::get('numbering.'.self::settingSuffix($docType).'_format');
                if (filled($configuredFormat) && $configuredFormat !== $cfg->format) {
                    $cfg->format = $configuredFormat;
                    $cfg->padding = self::paddingFromFormat((string) $configuredFormat);
                }
            }

            $now = now();
            $periodKey = match ($cfg->reset_period) {
                'YEARLY' => $now->format('Y'),
                'MONTHLY' => $now->format('Ym'),
                default => 'ALL',
            };

            if ($cfg->last_period !== $periodKey) {
                $cfg->current_seq = 0;
                $cfg->last_period = $periodKey;
            }

            $cfg->current_seq = $cfg->current_seq + 1;
            $cfg->save();

            $seq = str_pad((string) $cfg->current_seq, $cfg->padding, '0', STR_PAD_LEFT);
            $format = preg_replace_callback('/\{SEQ(?::(\d+))?\}/', fn ($match) => str_pad((string) $cfg->current_seq, (int) ($match[1] ?? $cfg->padding), '0', STR_PAD_LEFT), $cfg->format);

            return str_replace(
                ['{PREFIX}', '{Y}', '{YYYY}', '{YEAR}', '{M}', '{MM}', '{YM}', '{YMD}', '{TYPE}', '{DEPARTMENT}', '{COMPANY}', '{SITE}', '{MONTH_ROMAN}', '{DAY}'],
                [$cfg->doc_type === $cfg->format ? '' : self::prefixFor($cfg, $docType), $now->format('Y'), $now->format('Y'), $now->format('Y'), $now->format('m'), $now->format('m'), $now->format('Ym'), $now->format('Ymd'), $context['TYPE'] ?? '', $context['DEPARTMENT'] ?? '', $context['COMPANY'] ?? '', $context['SITE'] ?? '', self::romanMonth((int) $now->format('m')), $now->format('d')],
                $format
            );
        });
    }

    protected static function prefixFor(DocumentNumbering $cfg, string $docType): string
    {
        $suffix = self::settingSuffix($docType);

        return (string) Setting::get('numbering.'.$suffix.'_prefix', $docType);
    }

    private static function settingSuffix(string $docType): string
    {
        return match (strtoupper($docType)) {
            'INV', 'INVOICE' => 'invoice',
            'PO' => 'po',
            'PR' => 'pr',
            'DO' => 'do',
            'GR', 'GRN' => 'gr',
            'WB', 'WEIGHBRIDGE' => 'weighbridge',
            'JN', 'JOURNAL' => 'journal',
            'WO', 'WORK_ORDER' => 'work_order',
            'LETTER', 'SURAT' => 'letter',
            'KWITANSI', 'RECEIPT' => 'receipt',
            default => strtolower($docType),
        };
    }

    /**
     * Ensure a counter row exists with the given default format (used when
     * the format lives outside letter types, e.g. receipts).
     */
    public static function ensure(string $docType, string $defaultFormat, string $resetPeriod = 'MONTHLY'): DocumentNumbering
    {
        $row = DocumentNumbering::firstOrNew(['doc_type' => $docType, 'company_id' => null, 'site_id' => null]);
        if (! $row->exists) {
            $row->format = $defaultFormat;
            $row->current_seq = 0;
            $row->padding = 6;
            $row->reset_period = $resetPeriod;
            $row->save();
        }

        return $row;
    }

    public static function romanMonth(int $month): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][max(1, min(12, $month)) - 1];
    }

    private static function paddingFromFormat(string $format): int
    {
        preg_match('/\{SEQ:(\d+)\}/', $format, $match);

        return isset($match[1]) ? max(1, min(12, (int) $match[1])) : 6;
    }
}
