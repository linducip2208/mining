<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\CustomerDeposit;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\StockLedger;
use App\Models\StockReservation;
use App\Models\MiningActivity;
use App\Models\PurchaseRequest;
use App\Models\ProductionBatch;
use App\Models\SalesOrder;
use App\Models\WeighbridgeTicket;
use App\Models\WorkOrder;
use App\Models\JournalEntry;
use App\Models\Employee;
use App\Models\VendorBill;
use App\Models\PriceVariance;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from', now()->startOfMonth()->toDateString());
        $to = $request->date('to', now()->toDateString());
        $siteId = $request->integer('site_id') ?: null;

        $stockScope = fn ($q) => $siteId ? $q->where('site_id', $siteId) : $q;

        // OPERATIONAL
        $produksiHariIni = MiningActivity::whereDate('date', today())->when($siteId, fn ($q) => $q->where('site_id', $siteId))->whereIn('status', ['APPROVED', 'POSTED'])->sum('tonnage');
        $produksiBulanIni = MiningActivity::whereMonth('date', now()->month)->whereYear('date', now()->year)->when($siteId, fn ($q) => $q->where('site_id', $siteId))->whereIn('status', ['APPROVED', 'POSTED'])->sum('tonnage');
        $outputCrusher = ProductionBatch::whereMonth('date', now()->month)->when($siteId, fn ($q) => $q->where('site_id', $siteId))->where('status', 'POSTED')->sum('net_output');
        $tonnagePerSite = MiningActivity::select('sites.name', DB::raw('SUM(tonnage) total'))
            ->join('sites', 'sites.id', '=', 'mining_activities.site_id')
            ->whereMonth('date', now()->month)
            ->groupBy('sites.id', 'sites.name')->orderByDesc('total')->limit(6)->get();

        // INVENTORY
        $stockBalances = DB::table('stock_ledger')
            ->join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->select('items.name', DB::raw('SUM(qty_in - qty_out) as bal'))
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('bal')->limit(6)->get();
        $kritis = Item::where('min_stock', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(qty_in - qty_out),0) FROM stock_ledger WHERE stock_ledger.item_id = items.id) < min_stock')
            ->limit(8)->get();

        // SALES
        $salesHariIni = Invoice::whereDate('invoice_date', today())->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])->sum('total');
        $salesBulanIni = Invoice::whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year)->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])->sum('total');
        $piutang = Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->sum(DB::raw('total - paid_amount'));
        $outstandingInv = Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->count();
        $depositTotal = CustomerDeposit::selectRaw("SUM(CASE WHEN movement_type='DEPOSIT_IN' THEN amount ELSE 0 END) - SUM(CASE WHEN movement_type IN ('DEPOSIT_USED','DEPOSIT_REFUND') THEN amount ELSE 0 END) as bal")->value('bal') ?? 0;
        $topCustomers = Invoice::select('customers.name', DB::raw('SUM(total) total'))
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->whereMonth('invoice_date', now()->month)
            ->groupBy('customers.id', 'customers.name')->orderByDesc('total')->limit(5)->get();

        // FINANCE (tahan terhadap mapping yang belum dikonfigurasi)
        $mapOrNull = function (string $code) {
            try {
                return AccountingService::map($code);
            } catch (\InvalidArgumentException $e) {
                return null;
            }
        };
        $balOrZero = fn ($code, $from = null, $to = null) => $code ? AccountingService::balance($code, $from, $to) : 0;

        $revenue = -$balOrZero($mapOrNull('SALES_REVENUE'), $from, $to);
        $expense = $balOrZero($mapOrNull('ADMIN_EXPENSE'), $from, $to)
            + $balOrZero($mapOrNull('SALARY_EXPENSE'), $from, $to)
            + $balOrZero($mapOrNull('MAINTENANCE_EXPENSE'), $from, $to);
        $kas = $balOrZero($mapOrNull('CASH_MAIN'));

        // HR
        $employeeActive = Employee::where('status', 'ACTIVE')->count();
        $hadir = Employee::where('status', 'ACTIVE')->whereHas('attendances', fn ($q) => $q->whereDate('date', today())->where('status', 'PRESENT'))->count();

        // MAINTENANCE
        $woOpen = WorkOrder::whereIn('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'IN_PROGRESS'])->count();
        $downtime = WorkOrder::whereMonth('date', now()->month)->sum('downtime_hours');

        // APPROVALS
        $pendingApprovals = auth()->user()->isSuperAdmin()
            ? ApprovalRequest::where('status', 'PENDING')->orderBy('submitted_at')->limit(8)->get()
            : collect();

        // CHART DATA
        $prodTrend = MiningActivity::selectRaw('DATE(date) d, SUM(tonnage) t')
            ->whereDate('date', '>=', today()->subDays(13))
            ->whereIn('status', ['APPROVED', 'POSTED'])
            ->groupBy('d')->orderBy('d')->get();
        $salesTrend = Invoice::selectRaw('DATE(invoice_date) d, SUM(total) t')
            ->whereDate('invoice_date', '>=', today()->subDays(13))
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->groupBy('d')->orderBy('d')->get();
        $revExpTrend = JournalEntry::query()
            ->selectRaw('journal_entries.period, SUM(jl.debit) expense')
            ->join('journal_lines as jl', 'jl.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'jl.chart_of_account_id')
            ->where('journal_entries.status', 'POSTED')
            ->where('coa.type', 'EXPENSE')
            ->groupBy('journal_entries.period')->orderBy('period')
            ->limit(6)->get();

        $variances = PriceVariance::where('approval_status', 'PENDING')->latest()->limit(5)->get();

        return view('dashboard', compact(
            'produksiHariIni', 'produksiBulanIni', 'outputCrusher', 'tonnagePerSite',
            'stockBalances', 'kritis',
            'salesHariIni', 'salesBulanIni', 'piutang', 'outstandingInv', 'depositTotal', 'topCustomers',
            'revenue', 'expense', 'kas',
            'employeeActive', 'hadir',
            'woOpen', 'downtime',
            'pendingApprovals',
            'prodTrend', 'salesTrend', 'revExpTrend', 'variances'
        ));
    }
}
