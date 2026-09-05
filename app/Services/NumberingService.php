<?php

namespace App\Services;

use App\Models\DocumentNumbering;
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

            if (!$cfg) {
                $cfg = DocumentNumbering::create([
                    'company_id' => null,
                    'site_id' => null,
                    'doc_type' => $docType,
                    'format' => '{PREFIX}-{YM}--{SEQ}',
                    'current_seq' => 0,
                    'padding' => 6,
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

            return str_replace(
                ['{PREFIX}', '{Y}', '{M}', '{YM}', '{YMD}', '{SEQ}'],
                [$cfg->doc_type === $cfg->format ? '' : self::prefixFor($cfg, $docType), $now->format('Y'), $now->format('m'), $now->format('Ym'), $now->format('Ymd'), $seq],
                $cfg->format
            );
        });
    }

    protected static function prefixFor(DocumentNumbering $cfg, string $docType): string
    {
        // doc_type itself is the prefix (SO, DO, INV, PR, PO, GRN, WO, WB, JN)
        return $docType;
    }
}
