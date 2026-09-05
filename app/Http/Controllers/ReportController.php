<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Attendance;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\MiningActivity;
use App\Models\PayrollRun;
use App\Models\ProductionBatch;
use App\Models\VendorBill;
use App\Models\WorkOrder;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ExportsCsv;
    public function mining(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $siteId = $request->site_id;

        if ($request->boolean('export')) {
            $rows = MiningActivity::query()
                ->join('sites', 'sites.id', '=', 'mining_activities.site_id')
                ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
                ->whereBetween('date', [$from, $to])
                ->whereIn('mining_activities.status', ['APPROVED', 'POSTED'])
                ->selectRaw("sites.name, COUNT(*) trips, SUM(tonnage) tonnage, SUM(working_hours) hours")
                ->groupBy('sites.name')->get();
            return $this->exportCsv('laporan-tambang', ['Site', 'Trip', 'Tonase', 'Jam Kerja'],
                $rows->map(fn ($r) => [$r->name, $r->trips, $r->tonnage, $r->hours]));
        }

        $perSite = MiningActivity::query()
            ->join('sites', 'sites.id', '=', 'mining_activities.site_id')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereBetween('date', [$from, $to])
            ->whereIn('mining_activities.status', ['APPROVED', 'POSTED'])
            ->selectRaw("sites.name, COUNT(*) trips, SUM(tonnage) tonnage, SUM(working_hours) hours")
            ->groupBy('sites.name')->get();

        $perShift = MiningActivity::query()
            ->join('shifts', 'shifts.id', '=', 'mining_activities.shift_id')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereBetween('date', [$from, $to])
            ->whereIn('mining_activities.status', ['APPROVED', 'POSTED'])
            ->selectRaw("shifts.name, SUM(tonnage) tonnage")
            ->groupBy('shifts.name')->get();

        $perOperator = MiningActivity::query()
            ->join('employees', 'employees.id', '=', 'mining_activities.operator_id')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereBetween('date', [$from, $to])
            ->whereIn('mining_activities.status', ['APPROVED', 'POSTED'])
            ->selectRaw("employees.name, SUM(tonnage) tonnage, SUM(working_hours) hours, COUNT(*) trips")
            ->groupBy('employees.name')->orderByDesc('tonnage')->limit(15)->get();

        $perEquipment = MiningActivity::query()
            ->join('equipment', 'equipment.id', '=', 'mining_activities.equipment_id')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereBetween('date', [$from, $to])
            ->whereIn('mining_activities.status', ['APPROVED', 'POSTED'])
            ->selectRaw("equipment.name, SUM(tonnage) tonnage, SUM(working_hours) hours")
            ->groupBy('equipment.name')->orderByDesc('tonnage')->limit(15)->get();

        $this->auditReport($request, 'MINING');
        return view('reports.mining', compact('perSite', 'perShift', 'perOperator', 'perEquipment', 'from', 'to', 'siteId'));
    }

    public function production(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        if ($request->boolean('export')) {
            $rows = ProductionBatch::with(['crusher', 'shift'])
                ->whereBetween('date', [$from, $to])
                ->whereIn('status', ['APPROVED', 'POSTED'])
                ->orderByDesc('date')->get();
            return $this->exportCsv('laporan-produksi', ['Batch', 'Tanggal', 'Crusher', 'Input', 'Gross', 'Loss', 'Scrap', 'Net'],
                $rows->map(fn ($b) => [$b->number, $b->date?->format('Y-m-d'), $b->crusher?->name, $b->input_tonnage, $b->gross_output, $b->total_loss, $b->total_scrap, $b->net_output]));
        }

        $batches = ProductionBatch::with(['crusher', 'shift'])
            ->whereBetween('date', [$from, $to])
            ->whereIn('status', ['APPROVED', 'POSTED'])
            ->orderByDesc('date')->get();

        $lossSummary = $batches->flatMap->losses->groupBy('category')->map->sum('tonnage');

        $this->auditReport($request, 'PRODUCTION');
        return view('reports.production', compact('batches', 'lossSummary', 'from', 'to'));
    }

    public function inventory(Request $request)
    {
        $rows = \App\Models\StockLedger::query()
            ->join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_ledger.warehouse_id')
            ->selectRaw("items.code, items.name, items.min_stock, warehouses.name as warehouse,
                SUM(stock_ledger.qty_in) - SUM(stock_ledger.qty_out) as balance,
                (SELECT COALESCE(SUM(total_cost),0) FROM stock_ledger sl2 WHERE sl2.item_id = items.id) as cost_value")
            ->groupBy('items.code', 'items.name', 'items.min_stock', 'warehouses.name')
            ->havingRaw('SUM(stock_ledger.qty_in) - SUM(stock_ledger.qty_out) != 0')
            ->orderBy('items.name')->get();

        $critical = $rows->filter(fn ($r) => $r->min_stock > 0 && $r->balance < $r->min_stock);

        $this->auditReport($request, 'INVENTORY');
        return view('reports.inventory', compact('rows', 'critical'));
    }

    public function sales(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        if ($request->boolean('export')) {
            $rows = Invoice::query()
                ->join('customers', 'customers.id', '=', 'invoices.customer_id')
                ->whereBetween('invoice_date', [$from, $to])
                ->whereIn('invoices.status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
                ->selectRaw("customers.name, COUNT(*) invoices, SUM(invoices.total) total, SUM(invoices.paid_amount) paid")
                ->groupBy('customers.name')->orderByDesc('total')->limit(500)->get();
            return $this->exportCsv('laporan-penjualan', ['Customer', 'Faktur', 'Total', 'Dibayar', 'Outstanding'],
                $rows->map(fn ($r) => [$r->name, $r->invoices, $r->total, $r->paid, $r->total - $r->paid]));
        }

        $perCustomer = Invoice::query()
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->whereBetween('invoice_date', [$from, $to])
            ->whereIn('invoices.status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->selectRaw("customers.name, COUNT(*) invoices, SUM(invoices.total) total, SUM(invoices.paid_amount) paid")
            ->groupBy('customers.name')->orderByDesc('total')->limit(20)->get();

        $perItem = \App\Models\InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('items', 'items.id', '=', 'invoice_items.item_id')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->whereIn('invoices.status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->selectRaw("items.name, SUM(invoice_items.qty) qty, SUM(invoice_items.total_price) total")
            ->groupBy('items.name')->orderByDesc('total')->limit(20)->get();

        $outstanding = Invoice::with('customer')->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->orderBy('due_date')->get();

        $this->auditReport($request, 'SALES');
        return view('reports.sales', compact('perCustomer', 'perItem', 'outstanding', 'from', 'to'));
    }

    public function hr(Request $request)
    {
        $month = $request->period ?? now()->format('Y-m');
        [$y, $m] = explode('-', $month);

        $attendanceSummary = Attendance::query()
            ->join('employees', 'employees.id', '=', 'attendances.employee_id')
            ->whereYear('date', $y)->whereMonth('date', $m)
            ->selectRaw("employees.name, SUM(CASE WHEN attendances.status IN ('PRESENT','LATE') THEN 1 ELSE 0 END) present,
                SUM(CASE WHEN attendances.status='LATE' THEN 1 ELSE 0 END) late,
                SUM(attendances.overtime_minutes)/60 overtime_hours")
            ->groupBy('employees.name')->orderBy('employees.name')->limit(50)->get();

        $payrolls = PayrollRun::where('period', $month)->with('company')->get();

        $this->auditReport($request, 'HR');
        return view('reports.hr', compact('attendanceSummary', 'payrolls', 'month'));
    }

    public function maintenance(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $workOrders = WorkOrder::with(['equipment', 'asset'])
            ->whereBetween('date', [$from, $to])->orderByDesc('date')->get();

        $costByEquipment = $workOrders->groupBy(fn ($w) => $w->equipment?->name ?? $w->asset?->name ?? 'Lainnya')
            ->map(fn ($g) => $g->sum('actual_cost'));

        $downtimeByEquipment = $workOrders->groupBy(fn ($w) => $w->equipment?->name ?? 'Lainnya')
            ->map(fn ($g) => $g->sum('downtime_hours'));

        $this->auditReport($request, 'MAINTENANCE');
        return view('reports.maintenance', compact('workOrders', 'costByEquipment', 'downtimeByEquipment', 'from', 'to'));
    }

    protected function auditReport(Request $request, string $module): void
    {
        if ($request->boolean('export')) {
            AuditService::log('EXPORT', $module, null, null, null, ['filters' => $request->query()]);
        }
    }
    public function cashFlow(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $cashCoaIds = ChartOfAccount::whereIn('subtype', ['CASH', 'BANK'])->pluck('id');

        $inflows = \App\Models\JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.status', 'POSTED')
            ->whereBetween('journal_entries.journal_date', [$from, $to])
            ->whereIn('journal_lines.chart_of_account_id', $cashCoaIds)
            ->selectRaw("journal_entries.source_type,
                COALESCE(SUM(journal_lines.debit),0) as debit,
                COALESCE(SUM(journal_lines.credit),0) as credit")
            ->groupBy('journal_entries.source_type')
            ->get()
            ->map(function ($row) {
                $row->net = $row->debit - $row->credit; // debit to cash = inflow
                return $row;
            });

        $opening = (float) \App\Models\JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereIn('journal_lines.chart_of_account_id', $cashCoaIds)
            ->where('journal_entries.status', 'POSTED')
            ->whereDate('journal_entries.journal_date', '<', $from)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as net')
            ->value('net');

        $closing = $opening + $inflows->sum('net');

        $this->auditReport($request, 'CASH_FLOW');
        return view('finance.reports.cash-flow', compact('inflows', 'opening', 'closing', 'from', 'to'));
    }
}
