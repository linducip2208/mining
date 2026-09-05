<?php

namespace App\Services;

use App\Models\AccountingMapping;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Post a balanced journal entry. Throws on imbalance or closed period.
     *
     * @param array $lines each: ['code' => coa_code, 'debit' => 0, 'credit' => 0, 'memo' => ..., 'site_id' => ?, 'cost_center_id' => ?]
     */
    public static function post(
        int $companyId,
        string $date,
        array $lines,
        ?string $sourceType = null,
        $sourceId = null,
        ?string $sourceNumber = null,
        ?string $memo = null,
        ?string $docType = 'JN'
    ): JournalEntry {
        $lines = collect($lines)
            ->filter(fn ($l) => ((float) ($l['debit'] ?? 0)) != 0 || ((float) ($l['credit'] ?? 0)) != 0)
            ->values();

        if ($lines->count() < 2) {
            throw new \InvalidArgumentException('Journal minimal 2 baris.');
        }

        $totalDebit = $lines->sum(fn ($l) => round((float) ($l['debit'] ?? 0), 2));
        $totalCredit = $lines->sum(fn ($l) => round((float) ($l['credit'] ?? 0), 2));

        if (bccomp((string) $totalDebit, (string) $totalCredit, 2) !== 0) {
            throw new \DomainException('Jurnal tidak balance: Debit Rp ' . number_format($totalDebit, 2) . ' != Credit Rp ' . number_format($totalCredit, 2));
        }

        return DB::transaction(function () use ($companyId, $date, $lines, $totalDebit, $totalCredit, $sourceType, $sourceId, $sourceNumber, $memo, $docType) {
            $period = substr($date, 0, 7);
            self::assertPeriodOpen($companyId, $period);

            $entry = JournalEntry::create([
                'number' => NumberingService::generate($docType ?: 'JN', $companyId),
                'company_id' => $companyId,
                'journal_date' => $date,
                'period' => $period,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_number' => $sourceNumber,
                'memo' => $memo,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'POSTED',
                'posted_by' => auth()->id() ?? 1,
                'posted_at' => now(),
                'created_by' => auth()->id() ?? 1,
            ]);

            foreach ($lines as $l) {
                $coa = ChartOfAccount::where('code', $l['code'])->first();
                if (!$coa) {
                    throw new \InvalidArgumentException('COA tidak ditemukan: ' . ($l['code'] ?? '(null)'));
                }
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $coa->id,
                    'site_id' => $l['site_id'] ?? null,
                    'cost_center_id' => $l['cost_center_id'] ?? null,
                    'memo' => $l['memo'] ?? null,
                    'debit' => round((float) ($l['debit'] ?? 0), 2),
                    'credit' => round((float) ($l['credit'] ?? 0), 2),
                ]);
            }

            AuditService::log('POST', 'ACCOUNTING', $entry->id, JournalEntry::class, null, ['number' => $entry->number, 'source' => $sourceNumber, 'total' => $totalDebit]);

            return $entry;
        });
    }

    /**
     * Reversal creates a mirrored POSTED journal; original stays immutable.
     */
    public static function reverse(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        if ($entry->is_reversal) {
            throw new \DomainException('Jurnal reversal tidak dapat di-reverse lagi.');
        }
        if ($entry->status !== 'POSTED') {
            throw new \DomainException('Hanya jurnal POSTED yang dapat di-reverse.');
        }

        return DB::transaction(function () use ($entry, $reason) {
            self::assertPeriodOpen($entry->company_id, now()->format('Y-m'));

            $lines = $entry->lines->map(function (JournalLine $l) use ($reason) {
                return [
                    'code' => $l->chartOfAccount->code,
                    'debit' => $l->credit,
                    'credit' => $l->debit,
                    'memo' => 'Reversal: ' . ($l->memo ?: ($reason ?? '')),
                    'site_id' => $l->site_id,
                    'cost_center_id' => $l->cost_center_id,
                ];
            })->all();

            $rev = self::post(
                $entry->company_id,
                now()->toDateString(),
                $lines,
                $entry->source_type,
                $entry->source_id,
                $entry->source_number,
                'Reversal dari ' . $entry->number . ($reason ? ': ' . $reason : null),
                'JN'
            );
            $rev->is_reversal = true;
            $rev->reversal_of_id = $entry->id;
            $rev->save();

            return $rev;
        });
    }

    public static function assertPeriodOpen(int $companyId, string $period): void
    {
        $fp = FiscalPeriod::where('company_id', $companyId)->where('period', $period)->first();
        if ($fp && $fp->status !== 'OPEN') {
            throw new \DomainException("Periode {$period} sudah ditutup ({$fp->status}). Posting jurnal ditolak.");
        }
    }

    /**
     * Resolve COA code from configurable accounting mapping.
     */
    public static function map(string $mappingCode): string
    {
        $m = AccountingMapping::where('code', $mappingCode)->first();
        if (!$m) {
            throw new \InvalidArgumentException("Mapping akun '{$mappingCode}' belum dikonfigurasi. Atur di Settings > Accounting Mapping.");
        }
        return $m->chartOfAccount->code;
    }

    public static function balance(string $coaCode, ?string $from = null, ?string $to = null, array $extra = []): float
    {
        $q = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.status', 'POSTED')
            ->where('chart_of_accounts.code', $coaCode);

        if ($from) {
            $q->whereDate('journal_entries.journal_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('journal_entries.journal_date', '<=', $to);
        }
        foreach ($extra as $col => $val) {
            if ($val !== null) {
                $q->where($col, $val);
            }
        }

        $sum = $q->selectRaw('COALESCE(SUM(journal_lines.debit),0) d, COALESCE(SUM(journal_lines.credit),0) c')->first();
        return (float) $sum->d - (float) $sum->c;
    }
}
