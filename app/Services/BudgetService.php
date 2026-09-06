<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetCommitment;
use App\Models\BudgetLine;
use App\Models\JournalLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Budget vs Actual with commitments.
 * PR/PO approval creates COMMITTED records; vendor bills & journals
 * form ACTUALS live. Over-budget behavior follows setting
 * budget.enforce = off|warning|block (default warning).
 */
class BudgetService
{
    public static function enforceMode(): string
    {
        return strtolower((string) Setting::get('budget.enforce', 'warning'));
    }

    public static function activeBudget(int $companyId, ?int $siteId, string $type, int $year): ?Budget
    {
        return Budget::where('company_id', $companyId)
            ->where('type', $type)
            ->where('year', $year)
            ->whereIn('status', ['APPROVED', 'REVISED'])
            ->when($siteId, fn ($q) => $q->where(function ($w) use ($siteId) {
                $w->whereNull('site_id')->orWhere('site_id', $siteId);
            }))
            ->orderByRaw('site_id IS NULL')
            ->first();
    }

    /**
     * Actuals for a line: posted journal debits on the COA
     * (expenses/assets) within scope + period.
     */
    public static function actualForLine(BudgetLine $line): float
    {
        $budget = $line->budget;
        $q = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.status', 'POSTED')
            ->where('journal_lines.chart_of_account_id', $line->chart_of_account_id)
            ->where('journal_entries.company_id', $budget->company_id);
        if ($budget->site_id) {
            $q->where('journal_lines.site_id', $budget->site_id);
        }
        if ($line->cost_center_id) {
            $q->where('journal_lines.cost_center_id', $line->cost_center_id);
        }
        if ($line->period) {
            $q->where('journal_entries.period', $line->period);
        } else {
            $q->where('journal_entries.period', 'like', $budget->year . '-%');
        }
        // expenses & assets accumulate on debit side; revenue/others on credit
        $type = $line->chartOfAccount->type ?? 'EXPENSE';
        $row = $q->selectRaw('COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c')->first();
        $net = in_array($type, ['ASSET', 'EXPENSE']) ? ($row->d - $row->c) : ($row->c - $row->d);
        return round(max($net, 0), 2);
    }

    public static function committedForLine(BudgetLine $line): float
    {
        return (float) BudgetCommitment::where('budget_line_id', $line->id)
            ->where('status', 'COMMITTED')
            ->sum('amount');
    }

    public static function lineReport(BudgetLine $line): array
    {
        $budget = (float) $line->amount;
        $actual = self::actualForLine($line);
        $committed = self::committedForLine($line);
        $available = round($budget - $committed - $actual, 2);
        return [
            'line' => $line,
            'budget' => $budget,
            'committed' => round($committed, 2),
            'actual' => $actual,
            'available' => $available,
            'variance' => round($budget - $actual, 2),
            'used_pct' => $budget > 0 ? round(($committed + $actual) / $budget * 100, 2) : 0,
        ];
    }

    public static function budgetReport(Budget $budget): array
    {
        $rows = [];
        foreach ($budget->lines()->with('chartOfAccount')->get() as $line) {
            $rows[] = self::lineReport($line);
        }
        return [
            'budget' => $budget,
            'rows' => $rows,
            'totals' => [
                'budget' => round(collect($rows)->sum('budget'), 2),
                'committed' => round(collect($rows)->sum('committed'), 2),
                'actual' => round(collect($rows)->sum('actual'), 2),
                'available' => round(collect($rows)->sum('available'), 2),
            ],
        ];
    }

    /**
     * Check whether amount fits available budget for a COA+scope+period.
     * Returns ['ok'=>bool,'message'=>?]. No budget line = no control.
     */
    public static function check(int $companyId, ?int $siteId, int $coaId, string $periodMonth, float $amount): array
    {
        $year = (int) substr($periodMonth, 0, 4);
        $budget = Budget::where('company_id', $companyId)
            ->where('year', $year)
            ->whereIn('status', ['APPROVED', 'REVISED'])
            ->where(function ($q) use ($siteId) {
                $q->whereNull('site_id');
                if ($siteId) {
                    $q->orWhere('site_id', $siteId);
                }
            })
            ->orderByRaw('site_id IS NULL')
            ->first();
        if (!$budget) {
            return ['ok' => true, 'message' => null];
        }
        $line = $budget->lines()
            ->where('chart_of_account_id', $coaId)
            ->where(function ($q) use ($periodMonth) {
                $q->whereNull('period')->orWhere('period', $periodMonth);
            })
            ->orderByRaw('period IS NULL')
            ->first();
        if (!$line) {
            return ['ok' => true, 'message' => null];
        }
        $report = self::lineReport($line);
        if ($amount > $report['available'] + 0.01) {
            $msg = 'Melebihi budget tersedia ' . $budget->number . ' (' . $line->chartOfAccount->code . '): tersedia Rp ' . number_format($report['available'], 0, ',', '.') . ', dibutuhkan Rp ' . number_format($amount, 0, ',', '.') . '.';
            if (auth()->user()?->hasPermission('budget.override')) {
                return ['ok' => true, 'message' => $msg . ' (dilanjutkan dengan izin budget.override)'];
            }
            return ['ok' => false, 'message' => $msg];
        }
        return ['ok' => true, 'message' => null];
    }

    public static function assertAvailable(int $companyId, ?int $siteId, int $coaId, string $periodMonth, float $amount): ?string
    {
        $check = self::check($companyId, $siteId, $coaId, $periodMonth, $amount);
        $mode = self::enforceMode();
        if (!$check['ok'] && $mode === 'block') {
            throw new \DomainException($check['message']);
        }
        return $check['message']; // warning text or null
    }

    public static function commit(string $refType, $refId, ?string $refNumber, int $budgetId, ?int $lineId, float $amount): BudgetCommitment
    {
        return BudgetCommitment::updateOrCreate(
            ['ref_type' => $refType, 'ref_id' => $refId],
            [
                'budget_id' => $budgetId,
                'budget_line_id' => $lineId,
                'ref_number' => $refNumber,
                'amount' => round($amount, 2),
                'status' => 'COMMITTED',
                'created_by' => auth()->id() ?? 1,
            ]
        );
    }

    public static function release(string $refType, $refId): void
    {
        BudgetCommitment::where('ref_type', $refType)->where('ref_id', $refId)
            ->update(['status' => 'RELEASED']);
    }

    public static function consume(string $refType, $refId): void
    {
        BudgetCommitment::where('ref_type', $refType)->where('ref_id', $refId)
            ->update(['status' => 'CONSUMED']);
    }

    /** Find best budget line for a COA (for commitment attach). */
    public static function findLine(int $companyId, ?int $siteId, int $coaId, string $periodMonth): ?BudgetLine
    {
        $year = (int) substr($periodMonth, 0, 4);
        $budget = self::activeBudget($companyId, $siteId, 'OPEX', $year)
            ?? self::activeBudget($companyId, $siteId, 'CAPEX', $year);
        if (!$budget) {
            return null;
        }
        return $budget->lines()
            ->where('chart_of_account_id', $coaId)
            ->where(function ($q) use ($periodMonth) {
                $q->whereNull('period')->orWhere('period', $periodMonth);
            })
            ->orderByRaw('period IS NULL')
            ->first();
    }

    public static function commitPurchaseRequest(PurchaseRequest $pr): ?string
    {
        $coaId = \App\Models\ChartOfAccount::where('code', AccountingService::map('INVENTORY_GENERAL'))->value('id');
        $month = substr($pr->request_date instanceof \DateTimeInterface ? $pr->request_date->format('Y-m-d') : (string) $pr->request_date, 0, 7);
        $amount = (float) $pr->items()->get()->sum(fn ($i) => (float) $i->qty * (float) ($i->item?->standard_cost ?? 0));
        $line = self::findLine($pr->company_id, $pr->site_id, $coaId, $month);
        if (!$line) {
            return null;
        }
        $warn = self::assertAvailable($pr->company_id, $pr->site_id, $coaId, $month, $amount);
        self::commit('PURCHASE_REQUEST', $pr->id, $pr->number, $line->budget_id, $line->id, $amount);
        return $warn;
    }

    public static function commitPurchaseOrder(PurchaseOrder $po): ?string
    {
        $coaId = \App\Models\ChartOfAccount::where('code', AccountingService::map('INVENTORY_GENERAL'))->value('id');
        $month = substr($po->order_date instanceof \DateTimeInterface ? $po->order_date->format('Y-m-d') : (string) $po->order_date, 0, 7);
        $line = self::findLine($po->company_id, $po->site_id, $coaId, $month);
        if ($po->purchase_request_id) {
            self::release('PURCHASE_REQUEST', $po->purchase_request_id);
        }
        if (!$line) {
            return null;
        }
        $warn = self::assertAvailable($po->company_id, $po->site_id, $coaId, $month, (float) $po->subtotal);
        self::commit('PURCHASE_ORDER', $po->id, $po->number, $line->budget_id, $line->id, (float) $po->subtotal);
        return $warn;
    }
}
