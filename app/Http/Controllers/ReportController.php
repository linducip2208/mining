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

    protected function scopedSiteIds(?int $siteId): ?array
    {
        $allowed = auth()->user()?->accessibleSiteIds();
        if ($allowed !== null && $siteId !== null && !in_array($siteId, $allowed)) {
            abort(403, 'Site di luar scope akses Anda.');
        }
        if ($siteId !== null) {
            return [$siteId];
        }
        return $allowed;
    }

    public function fleet(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $sites = $this->scopedSiteIds($request->site_id ? (int) $request->site_id : null);

        $summary = \App\Services\FleetService::fleetSummary(
            $request->company_id ? (int) $request->company_id : null,
            $request->site_id ? (int) $request->site_id : null,
            $from, $to
        );
        $rows = collect($summary['rows'] ?? []);
        if ($sites !== null) {
            $rows = $rows->filter(fn ($r) => in_array($r['unit']->site_id, $sites))->values();
        }

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-armada', ['Unit', 'Status', 'Operasi(H)', 'Idle(H)', 'Downtime(H)', 'PA%', 'Util%', 'Biaya', 'Rp/Jam'],
                $rows->map(fn ($r) => [$r['unit']->code, $r['unit']->status, $r['kpi']['operating_hours'], $r['kpi']['idle_hours'], $r['kpi']['downtime_hours'], $r['kpi']['pa_pct'], $r['kpi']['utilization_pct'], $r['kpi']['total_cost'], $r['kpi']['cost_per_hour']]));
        }

        $this->auditReport($request, 'FLEET');
        $sites_list = \App\Models\Site::pluck('name', 'id')->all();
        return view('reports.fleet', ['rows' => $rows, 'from' => $from, 'to' => $to, 'sites_list' => $sites_list]);
    }

    public function fuel(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $sites = $this->scopedSiteIds($request->site_id ? (int) $request->site_id : null);

        $issues = \App\Services\FuelService::consumptionReport(
            $request->company_id ? (int) $request->company_id : null,
            $request->site_id ? (int) $request->site_id : null,
            $from, $to
        );
        if ($sites !== null) {
            $issues = $issues->filter(fn ($i) => in_array($i->site_id, $sites))->values();
        }
        $perUnit = $issues->groupBy(fn ($i) => $i->equipment?->code ?? ($i->vehicle_plate ?? 'Lainnya'))
            ->map(fn ($g) => ['liter' => round($g->sum('liter'), 1), 'cost' => round($g->sum('total_cost'), 0), 'count' => $g->count()])
            ->sortByDesc('liter');
        $anomalies = $issues->filter(fn ($i) => in_array($i->variance_status, ['WARNING', 'CRITICAL']))->values();

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-bbm', ['Tanggal', 'Nomor', 'Unit', 'Liter', 'Biaya', 'L/H', 'Variansi'],
                $issues->map(fn ($i) => [$i->issue_date, $i->number, $i->equipment?->code ?? $i->vehicle_plate, $i->liter, $i->total_cost, $i->liter_per_hour, $i->variance_status]));
        }

        $this->auditReport($request, 'FUEL');
        return view('reports.fuel', compact('issues', 'perUnit', 'anomalies', 'from', 'to'));
    }

    public function tire(Request $request)
    {
        $rows = \App\Models\Tire::with(['equipment', 'movements'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('serial_no')->get()->map(function ($t) {
                $repairs = $t->movements->where('movement_type', 'REPAIR')->sum('cost');
                $life = 0;
                if ($t->install_hm && $t->equipment) {
                    $life = max((float) ($t->equipment->meter_reading ?? $t->install_hm) - (float) $t->install_hm, 0);
                }
                return [
                    'tire' => $t,
                    'repairs' => round($repairs, 0),
                    'total_cost' => round((float) $t->purchase_cost + $repairs, 0),
                    'life_hm' => round($life, 1),
                    'cost_per_hm' => $life > 0 ? round(((float) $t->purchase_cost + $repairs) / $life, 0) : null,
                ];
            });

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-ban', ['Seri', 'Merek/Ukuran', 'Status', 'Unit', 'Biaya Beli', 'Repair', 'Total Biaya', 'Lifetime HM', 'Rp/HM'],
                $rows->map(fn ($r) => [$r['tire']->serial_no, trim($r['tire']->brand . ' ' . $r['tire']->size), $r['tire']->status, $r['tire']->equipment?->code, $r['tire']->purchase_cost, $r['repairs'], $r['total_cost'], $r['life_hm'], $r['cost_per_hm']]));
        }

        $this->auditReport($request, 'TIRE');
        return view('reports.tire', compact('rows'));
    }

    public function dispatch(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $sites = $this->scopedSiteIds($request->site_id ? (int) $request->site_id : null);

        $trips = \App\Models\DispatchTrip::with(['truck', 'route'])
            ->whereBetween('trip_date', [$from, $to])
            ->when($sites !== null, fn ($q) => $q->whereIn('site_id', $sites))
            ->get();
        $done = $trips->where('status', 'COMPLETED')->values();
        $perTruck = $done->groupBy(fn ($t) => $t->truck?->code ?? 'Lainnya')
            ->map(fn ($g) => [
                'trips' => $g->count(),
                'tons' => round($g->sum('tonnage'), 1),
                'ton_per_trip' => $g->count() ? round($g->sum('tonnage') / $g->count(), 2) : 0,
                'cycle_avg' => round($g->map(fn ($t) => $t->cycleStats()['cycle_min'])->filter()->avg() ?? 0, 1),
            ])->sortByDesc('tons');
        $perRoute = $done->groupBy(fn ($t) => $t->route?->name ?? 'Lainnya')
            ->map(fn ($g) => [
                'trips' => $g->count(),
                'tons' => round($g->sum('tonnage'), 1),
                'ton_km' => round($g->sum(fn ($t) => (float) $t->tonnage * (float) ($t->route?->distance_km ?? 0)), 1),
            ])->sortByDesc('tons');

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-dispatch', ['Tanggal', 'Trip', 'Truk', 'Rute', 'Tonase', 'Status'],
                $trips->map(fn ($t) => [$t->trip_date, $t->number, $t->truck?->code, $t->route?->name, $t->tonnage, $t->status]));
        }

        $this->auditReport($request, 'DISPATCH');
        return view('reports.dispatch', compact('perTruck', 'perRoute', 'trips', 'done', 'from', 'to'));
    }

    public function stockpile(Request $request)
    {
        $sites = $this->scopedSiteIds($request->site_id ? (int) $request->site_id : null);
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $piles = \App\Models\Stockpile::with(['site', 'item'])
            ->when($sites !== null, fn ($q) => $q->whereIn('site_id', $sites))
            ->orderBy('code')->get()->map(function ($p) {
                $p->balance = \App\Services\StockpileService::balance($p->id);
                return $p;
            });
        $surveys = \App\Models\StockpileSurvey::with('stockpile')
            ->whereBetween('survey_date', [$from, $to])
            ->when($sites !== null, fn ($q) => $q->whereHas('stockpile', fn ($w) => $w->whereIn('site_id', $sites)))
            ->orderByDesc('survey_date')->get();

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-stockpile', ['Pile', 'Site', 'Saldo Sistem', 'Survei', 'Variansi', 'Var%', 'Status'],
                $surveys->map(fn ($s) => [$s->stockpile?->code, $s->stockpile?->site?->name, $s->system_balance, $s->survey_balance, $s->variance, $s->variance_pct, $s->status]));
        }

        $this->auditReport($request, 'STOCKPILE');
        return view('reports.stockpile', compact('piles', 'surveys', 'from', 'to'));
    }

    public function quality(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $samples = \App\Models\QcSample::with(['tests.parameter', 'item'])
            ->whereBetween('sample_date', [$from, $to])->get();
        $total = $samples->count();
        $passRate = $total ? round($samples->where('status', 'PASS')->count() / $total * 100, 1) : 0;
        $perParam = \App\Models\QcTest::with('parameter')
            ->whereHas('sample', fn ($q) => $q->whereBetween('sample_date', [$from, $to]))
            ->get()->groupBy(fn ($t) => $t->parameter?->code ?? '?')
            ->map(fn ($g) => [
                'tests' => $g->count(),
                'fails' => $g->where('result', 'FAIL')->count(),
                'fail_rate' => $g->count() ? round($g->where('result', 'FAIL')->count() / $g->count() * 100, 1) : 0,
            ])->sortByDesc('fail_rate');
        $holds = \App\Models\QualityHold::where('status', 'HOLD')->count();

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-quality', ['Sampel', 'Tanggal', 'Material', 'Status'],
                $samples->map(fn ($s) => [$s->number, $s->sample_date, $s->item?->name, $s->status]));
        }

        $this->auditReport($request, 'QUALITY');
        return view('reports.quality', compact('samples', 'total', 'passRate', 'perParam', 'holds', 'from', 'to'));
    }

    public function contract(Request $request)
    {
        $customers = \App\Models\CustomerContract::with(['customer', 'item'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->get()->map(fn ($c) => ['contract' => $c, 'real' => $c->realization()]);
        $suppliers = \App\Models\SupplierContract::with(['supplier', 'item'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->get()->map(fn ($c) => ['contract' => $c, 'real' => $c->realization()]);
        $haulings = \App\Models\HaulingContract::with(['supplier', 'route'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->get()->map(fn ($c) => ['contract' => $c, 'real' => \App\Services\ContractService::haulingRealization($c)]);

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-kontrak', ['Nomor', 'Pihak', 'Volume', 'Terealisasi', 'Sisa', 'Status'],
                $customers->map(fn ($r) => [$r['contract']->number, $r['contract']->customer?->name, $r['real']['contract_qty'], $r['real']['delivered_qty'], $r['real']['remaining_qty'], $r['contract']->status]));
        }

        $this->auditReport($request, 'CONTRACT');
        return view('reports.contract', compact('customers', 'suppliers', 'haulings'));
    }

    public function budget(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $budgets = \App\Models\Budget::with(['company', 'site'])
            ->where('year', $year)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->get()->map(fn ($b) => ['budget' => $b, 'report' => \App\Services\BudgetService::budgetReport($b)]);
        $totals = [
            'budget' => round($budgets->sum(fn ($r) => $r['report']['totals']['budget']), 0),
            'committed' => round($budgets->sum(fn ($r) => $r['report']['totals']['committed']), 0),
            'actual' => round($budgets->sum(fn ($r) => $r['report']['totals']['actual']), 0),
            'available' => round($budgets->sum(fn ($r) => $r['report']['totals']['available']), 0),
        ];

        if ($request->boolean('export')) {
            return $this->exportCsv('laporan-budget', ['Budget', 'Tahun', 'Tipe', 'Pagu', 'Komitmen', 'Aktual', 'Sisa', 'Status'],
                $budgets->map(fn ($r) => [$r['budget']->number, $r['budget']->year, $r['budget']->type, $r['report']['totals']['budget'], $r['report']['totals']['committed'], $r['report']['totals']['actual'], $r['report']['totals']['available'], $r['budget']->status]));
        }

        $this->auditReport($request, 'BUDGET');
        return view('reports.budget', compact('budgets', 'totals', 'year'));
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
