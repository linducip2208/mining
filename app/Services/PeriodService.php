<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Fiscal period lifecycle + year-end closing + fixed-asset depreciation.
 * Closed periods reject ALL postings (enforced in AccountingService).
 * Reopen requires fiscal.reopen permission (checked by caller) + audit.
 */
class PeriodService
{
    public static function closePeriod(int $companyId, string $period): FiscalPeriod
    {
        return DB::transaction(function () use ($companyId, $period) {
            $fp = FiscalPeriod::firstOrCreate(
                ['company_id' => $companyId, 'period' => $period],
                ['status' => 'OPEN']
            );
            if ($fp->status === 'CLOSED') {
                throw new \DomainException("Periode {$period} sudah ditutup.");
            }
            // integrity: period trial balance must balance before closing
            $sum = \App\Models\JournalLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_entries.status', 'POSTED')
                ->where('journal_entries.company_id', $companyId)
                ->where('journal_entries.period', $period)
                ->selectRaw('COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c')
                ->first();
            if (bccomp((string) $sum->d, (string) $sum->c, 2) !== 0) {
                throw new \DomainException("Periode {$period} tidak balance — closing ditolak.");
            }
            $fp->update(['status' => 'CLOSED', 'closed_by' => auth()->id(), 'closed_at' => now()]);
            AuditService::log('CLOSE', 'FISCAL', $fp->id, FiscalPeriod::class, ['status' => 'OPEN'], ['status' => 'CLOSED']);
            return $fp->fresh();
        });
    }

    public static function reopenPeriod(int $companyId, string $period, ?string $reason = null): FiscalPeriod
    {
        return DB::transaction(function () use ($companyId, $period, $reason) {
            $fp = FiscalPeriod::where('company_id', $companyId)->where('period', $period)->firstOrFail();
            if ($fp->status !== 'CLOSED') {
                throw new \DomainException("Periode {$period} tidak dalam status CLOSED.");
            }
            $fp->update(['status' => 'OPEN', 'closed_by' => null, 'closed_at' => null]);
            AuditService::log('REOPEN', 'FISCAL', $fp->id, FiscalPeriod::class, ['status' => 'CLOSED'], ['status' => 'OPEN'], $reason ?? 'Reopen periode');
            return $fp->fresh();
        });
    }

    /**
     * Year-end closing: transfer revenue & expense balances to
     * retained earnings (3-2000), then close all 12 periods.
     * Idempotent per year (guarded by CLOSE source journal).
     */
    public static function closeYear(int $companyId, int $year): JournalEntry
    {
        return DB::transaction(function () use ($companyId, $year) {
            $exists = JournalEntry::where('company_id', $companyId)
                ->where('source_type', 'YEAR_CLOSE')
                ->where('source_number', 'CLOSE-' . $year)
                ->where('status', 'POSTED')
                ->exists();
            if ($exists) {
                throw new \DomainException("Tahun {$year} sudah di-closing.");
            }

            $date = sprintf('%04d-12-31', $year);
            $lines = [];

            // company scoping: global COA (company_id null) + own company
            $revenues = ChartOfAccount::where('type', 'REVENUE')
                ->where(function ($q) use ($companyId) {
                    $q->whereNull('company_id')->orWhere('company_id', $companyId);
                })->get();
            foreach ($revenues as $coa) {
                $bal = self::coaYearBalance($companyId, $coa->id, $year); // credit-positive => negative number
                if (abs($bal) > 0.005) {
                    $lines[] = ['code' => $coa->code, 'debit' => -$bal, 'credit' => 0, 'memo' => 'Tutup pendapatan ' . $year];
                }
            }
            $expenses = ChartOfAccount::where('type', 'EXPENSE')
                ->where(function ($q) use ($companyId) {
                    $q->whereNull('company_id')->orWhere('company_id', $companyId);
                })->get();
            foreach ($expenses as $coa) {
                $bal = self::coaYearBalance($companyId, $coa->id, $year); // debit-positive
                if (abs($bal) > 0.005) {
                    $lines[] = ['code' => $coa->code, 'debit' => 0, 'credit' => $bal, 'memo' => 'Tutup beban ' . $year];
                }
            }
            if (empty($lines)) {
                throw new \DomainException("Tidak ada saldo L/R tahun {$year} untuk di-closing.");
            }
            // balance against retained earnings
            $debits = collect($lines)->sum('debit');
            $credits = collect($lines)->sum('credit');
            $diff = round($debits - $credits, 2);
            $reCoa = ChartOfAccount::where('code', '3-2000')->first();
            if (!$reCoa) {
                throw new \DomainException('Akun Laba Ditahan (3-2000) belum dikonfigurasi.');
            }
            if ($diff > 0) {
                $lines[] = ['code' => '3-2000', 'debit' => 0, 'credit' => $diff, 'memo' => 'Laba bersih ' . $year];
            } else {
                $lines[] = ['code' => '3-2000', 'debit' => abs($diff), 'credit' => 0, 'memo' => 'Rugi bersih ' . $year];
            }

            $journal = AccountingService::post($companyId, $date, $lines, 'YEAR_CLOSE', null, 'CLOSE-' . $year, 'Closing tahun ' . $year, 'JN');

            foreach (range(1, 12) as $m) {
                $period = sprintf('%04d-%02d', $year, $m);
                $fp = FiscalPeriod::firstOrCreate(['company_id' => $companyId, 'period' => $period], ['status' => 'OPEN']);
                if ($fp->status !== 'CLOSED') {
                    $fp->update(['status' => 'CLOSED', 'closed_by' => auth()->id(), 'closed_at' => now()]);
                }
            }
            AuditService::log('CLOSE', 'FISCAL', $journal->id, JournalEntry::class, null, ['year_close' => $year]);
            return $journal;
        });
    }

    protected static function coaYearBalance(int $companyId, int $coaId, int $year): float
    {
        $row = \App\Models\JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'POSTED')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_lines.chart_of_account_id', $coaId)
            ->where('journal_entries.period', 'like', $year . '-%')
            ->selectRaw('COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c')
            ->first();
        return round((float) $row->d - (float) $row->c, 2);
    }

    /**
     * Monthly fixed-asset depreciation run (straight line).
     * Idempotent per company+period (guarded by DEPRECIATION source journal).
     */
    public static function depreciationRun(int $companyId, string $period): JournalEntry
    {
        return DB::transaction(function () use ($companyId, $period) {
            AccountingService::assertPeriodOpen($companyId, $period);
            $exists = JournalEntry::where('company_id', $companyId)
                ->where('source_type', 'DEPRECIATION')
                ->where('period', $period)
                ->where('status', 'POSTED')
                ->exists();
            if ($exists) {
                throw new \DomainException("Penyusutan periode {$period} sudah diposting.");
            }

            [$y, $m] = explode('-', $period);
            $date = \Carbon\Carbon::create((int) $y, (int) $m, 1)->endOfMonth()->toDateString();
            $assets = Asset::where('company_id', $companyId)
                ->whereNotIn('status', ['DISPOSED', 'RETIRED'])
                ->where('acquisition_cost', '>', 0)
                ->where('useful_life_years', '>', 0)
                ->get();

            $lines = [];
            foreach ($assets as $asset) {
                $monthly = round((float) $asset->acquisition_cost / ((int) $asset->useful_life_years * 12), 2);
                if ($monthly <= 0) {
                    continue;
                }
                // stop when fully depreciated
                if ((float) $asset->accumulated_depreciation + $monthly > (float) $asset->acquisition_cost + 0.01) {
                    $monthly = round((float) $asset->acquisition_cost - (float) $asset->accumulated_depreciation, 2);
                }
                if ($monthly <= 0) {
                    continue;
                }
                $lines[] = [
                    'code' => AccountingService::map('DEPRECIATION_EXPENSE'),
                    'debit' => $monthly,
                    'memo' => 'Penyusutan ' . $asset->code,
                    'site_id' => $asset->site_id,
                ];
                $lines[] = [
                    'code' => AccountingService::map('ACCUM_DEP'),
                    'credit' => $monthly,
                    'memo' => 'Akum. penyusutan ' . $asset->code,
                    'site_id' => $asset->site_id,
                ];
                $asset->increment('accumulated_depreciation', $monthly);
            }

            if (empty($lines)) {
                throw new \DomainException('Tidak ada aset yang dapat disusutkan periode ini.');
            }

            $journal = AccountingService::post($companyId, $date, $lines, 'DEPRECIATION', null, 'DEPR-' . $period, 'Penyusutan aset ' . $period, 'JN');
            AuditService::log('POST', 'FISCAL', $journal->id, JournalEntry::class, null, ['depreciation' => $period]);
            return $journal;
        });
    }
}
