<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalLine;
use App\Models\VendorBill;
use Illuminate\Http\Request;

class FinanceReportController extends Controller
{
    use AppliesDataScope;

    public function trialBalance(Request $request)
    {
        $from = $request->from ?? now()->startOfYear()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $rows = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.status', 'POSTED')
            ->whereBetween('journal_entries.journal_date', [$from, $to])
            ->selectRaw('chart_of_accounts.code, chart_of_accounts.name, chart_of_accounts.type,
                COALESCE(SUM(journal_lines.debit),0) as debit,
                COALESCE(SUM(journal_lines.credit),0) as credit')
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code');

        $this->scopeJournalJoin($rows);
        $rows = $rows->get();

        $totalDebit = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');
        $balanced = bccomp((string) $totalDebit, (string) $totalCredit, 2) === 0;

        return view('finance.reports.trial-balance', compact('rows', 'from', 'to', 'totalDebit', 'totalCredit', 'balanced'));
    }

    public function ledger(Request $request)
    {
        $from = $request->from ?? now()->startOfYear()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $coaId = $request->chart_of_account_id;

        $lines = collect();
        $coa = $coaId ? ChartOfAccount::find($coaId) : null;

        if ($coaId) {
            $lines = JournalLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_entries.status', 'POSTED')
                ->where('journal_lines.chart_of_account_id', $coaId)
                ->whereBetween('journal_entries.journal_date', [$from, $to])
                ->selectRaw('journal_entries.journal_date, journal_entries.number, journal_entries.memo, journal_lines.debit, journal_lines.credit')
                ->orderBy('journal_entries.journal_date');
            $this->scopeJournalJoin($lines);
            $lines = $lines->get();
            $running = 0;
            foreach ($lines as $l) {
                $running += $l->debit - $l->credit;
                $l->balance = $running;
            }
        }

        return view('finance.reports.ledger', [
            'lines' => $lines, 'coa' => $coa, 'from' => $to ? $from : $from, 'to' => $to,
            'coas' => ChartOfAccount::where('is_postable', true)->orderBy('code')->get(),
        ]);
    }

    public function profitLoss(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $revenues = $this->typeBalances('REVENUE', $from, $to);
        $expenses = $this->typeBalances('EXPENSE', $from, $to);
        $totalRevenue = $revenues->sum('balance');
        $totalExpense = $expenses->sum('balance');
        $netIncome = $totalRevenue - $totalExpense;

        return view('finance.reports.pl', compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome', 'from', 'to'));
    }

    public function balanceSheet(Request $request)
    {
        $asOf = $request->as_of ?? now()->toDateString();

        $assets = $this->typeBalances('ASSET', null, $asOf);
        $liabilities = $this->typeBalances('LIABILITY', null, $asOf);
        $equity = $this->typeBalances('EQUITY', null, $asOf);

        // net income YTD as retained earnings component
        $start = now()->startOfYear()->toDateString();
        $revenues = $this->typeBalances('REVENUE', $start, $asOf);
        $expenses = $this->typeBalances('EXPENSE', $start, $asOf);
        $netIncome = $revenues->sum('balance') - $expenses->sum('balance');

        $totalAssets = $assets->sum('balance');
        $totalLiab = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $netIncome;
        $balanced = bccomp((string) $totalAssets, (string) ($totalLiab + $totalEquity), 2) === 0;

        return view('finance.reports.balance-sheet', compact('assets', 'liabilities', 'equity', 'netIncome', 'totalAssets', 'totalLiab', 'totalEquity', 'balanced', 'asOf'));
    }

    public function arAging(Request $request)
    {
        $invoices = Invoice::with('customer')
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID']);
        $this->applyCompanyScope($invoices);
        $invoices = $invoices->get();

        $buckets = [
            'current' => ['label' => 'Belum Jatuh Tempo', 'total' => 0, 'items' => collect()],
            'd1_30' => ['label' => '1-30 Hari', 'total' => 0, 'items' => collect()],
            'd31_60' => ['label' => '31-60 Hari', 'total' => 0, 'items' => collect()],
            'd61_90' => ['label' => '61-90 Hari', 'total' => 0, 'items' => collect()],
            'd90' => ['label' => '> 90 Hari', 'total' => 0, 'items' => collect()],
        ];

        foreach ($invoices as $inv) {
            $outstanding = (float) $inv->total - (float) $inv->paid_amount;
            $days = now()->diffInDays($inv->due_date ?? $inv->invoice_date, false) * -1;
            $key = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => 'd1_30',
                $days <= 60 => 'd31_60',
                $days <= 90 => 'd61_90',
                default => 'd90',
            };
            $buckets[$key]['total'] += $outstanding;
            $buckets[$key]['items']->push($inv);
        }

        return view('finance.reports.aging', ['buckets' => $buckets, 'side' => 'AR', 'title' => 'Umur Piutang (AR Aging)']);
    }

    public function apAging(Request $request)
    {
        $bills = VendorBill::with('supplier')
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID']);
        $this->applyCompanyScope($bills);
        $bills = $bills->get();

        $buckets = [
            'current' => ['label' => 'Belum Jatuh Tempo', 'total' => 0, 'items' => collect()],
            'd1_30' => ['label' => '1-30 Hari', 'total' => 0, 'items' => collect()],
            'd31_60' => ['label' => '31-60 Hari', 'total' => 0, 'items' => collect()],
            'd61_90' => ['label' => '61-90 Hari', 'total' => 0, 'items' => collect()],
            'd90' => ['label' => '> 90 Hari', 'total' => 0, 'items' => collect()],
        ];

        foreach ($bills as $bill) {
            $outstanding = (float) $bill->total - (float) $bill->paid_amount;
            $days = now()->diffInDays($bill->due_date ?? $bill->bill_date, false) * -1;
            $key = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => 'd1_30',
                $days <= 60 => 'd31_60',
                $days <= 90 => 'd61_90',
                default => 'd90',
            };
            $buckets[$key]['total'] += $outstanding;
            $buckets[$key]['items']->push($bill);
        }

        return view('finance.reports.aging', ['buckets' => $buckets, 'side' => 'AP', 'title' => 'Umur Utang (AP Aging)']);
    }

    protected function typeBalances(string $type, ?string $from, string $to)
    {
        $q = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.status', 'POSTED')
            ->whereDate('journal_entries.journal_date', '<=', $to)
            ->where('chart_of_accounts.type', $type);

        if ($from) {
            $q->whereDate('journal_entries.journal_date', '>=', $from);
        }
        $this->scopeJournalJoin($q);

        return $q->selectRaw('chart_of_accounts.code, chart_of_accounts.name,
                COALESCE(SUM(journal_lines.debit),0) as debit, COALESCE(SUM(journal_lines.credit),0) as credit,
                COALESCE(SUM(journal_lines.debit),0) - COALESCE(SUM(journal_lines.credit),0) as balance')
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name')
            ->orderBy('chart_of_accounts.code')
            ->get();
    }

    /**
     * Data scope on joined journal queries (journal_entries.company_id).
     */
    protected function scopeJournalJoin($query): void
    {
        $companies = auth()->user()?->accessibleCompanyIds();
        if ($companies !== null) {
            $query->whereIn('journal_entries.company_id', $companies);
        }
    }
}
