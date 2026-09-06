<?php

namespace App\Services;

use App\Models\DocumentNumbering;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    /**
     * Generate next document number, concurrency-safe (row lock + period reset).
     * Format placeholders: {PREFIX} {Y} {M} {YM} {YMD} {SEQ}
     */
    public static function generate(string $docType, ?int $companyId = null, ?int $siteId = null): string
    {
        return DB::transaction(function () use ($docType, $companyId, $siteId) {
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
                ['{PREFIX}', '{Y}', '{YYYY}', '{M}', '{MM}', '{YM}', '{YMD}'],
                [$cfg->doc_type === $cfg->format ? '' : self::prefixFor($cfg, $docType), $now->format('Y'), $now->format('Y'), $now->format('m'), $now->format('m'), $now->format('Ym'), $now->format('Ymd')],
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
            default => strtolower($docType),
        };
    }

    private static function paddingFromFormat(string $format): int
    {
        preg_match('/\{SEQ:(\d+)\}/', $format, $match);

        return isset($match[1]) ? max(1, min(12, (int) $match[1])) : 6;
    }
}
