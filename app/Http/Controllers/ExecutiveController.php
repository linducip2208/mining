<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CustomerDeposit;
use App\Models\Division;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\MiningActivity;
use App\Models\Pit;
use App\Models\ProductionBatch;
use App\Models\Site;
use App\Models\VendorBill;
use App\Models\WorkOrder;
use App\Services\CostEngine;
use App\Services\FleetService;
use App\Services\HseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Executive Command Center: one screen, real DB, drill-down links.
 * Modes: today | yesterday | mtd | ytd | custom.
 */
class ExecutiveController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->get('mode', 'mtd');
        [$from, $to] = $this->resolvePeriod($mode, $request->from, $request->to);

        $companyId = $request->integer('company_id') ?: null;
        $siteId = $request->integer('site_id') ?: null;
        $pitId = $request->integer('pit_id') ?: null;
        $productId = $request->integer('product_id') ?: null;

        $mine = MiningActivity::whereIn('status', ['APPROVED', 'POSTED'])
            ->whereBetween('date', [$from, $to])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->when($pitId, fn ($q) => $q->where('pit_id', $pitId))
            ->selectRaw('COALESCE(SUM(tonnage),0) tons, COALESCE(SUM(working_hours),0) hours')
            ->first();

        $prod = ProductionBatch::where('status', 'POSTED')
            ->whereBetween('date', [$from, $to])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->selectRaw('COALESCE(SUM(net_output),0) net, COALESCE(SUM(total_loss),0) loss, COALESCE(SUM(total_scrap),0) scrap')
            ->first();

        $sales = Invoice::whereIn('invoices.status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->join('sales_orders', 'sales_orders.id', '=', 'invoices.sales_order_id')
            ->when($companyId, fn ($q) => $q->where('invoices.company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('sales_orders.site_id', $siteId))
            ->when($productId, function ($q) use ($productId) {
                $q->whereExists(function ($w) use ($productId) {
                    $w->select(DB::raw(1))->from('invoice_items')
                        ->whereColumn('invoice_items.invoice_id', 'invoices.id')
                        ->where('invoice_items.item_id', $productId);
                });
            })
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(invoices.total),0) revenue')
            ->first();

        $cost = CostEngine::compute($companyId, $siteId, $from, $to);

        $fuel = \App\Models\FuelIssue::where('status', 'POSTED')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereBetween('issue_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(liter),0) liter, COALESCE(SUM(total_cost),0) cost')
            ->first();

        $fleet = FleetService::fleetSummary($companyId, $siteId, $from, $to);

        $stockValue = \App\Models\StockLedger::join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->when($siteId, fn ($q) => $q->where('stock_ledger.site_id', $siteId))
            ->selectRaw('COALESCE(SUM(total_cost),0) v')->value('v') ?? 0;

        $ar = (float) Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('COALESCE(SUM(total - paid_amount),0) b')->value('b');
        $ap = (float) VendorBill::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->selectRaw('COALESCE(SUM(total - paid_amount),0) b')->value('b');

        $criticalStock = Item::where('min_stock', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(qty_in - qty_out),0) FROM stock_ledger WHERE stock_ledger.item_id = items.id) < min_stock')
            ->count();

        $expiredPermits = \App\Models\ComplianceRegister::where('status', 'EXPIRED')->count()
            + \App\Models\ComplianceRegister::where('status', 'EXPIRING_SOON')->count();

        $hse = HseService::dashboard($companyId, $siteId, $from, $to);

        $pendingApprovals = \App\Models\ApprovalRequest::where('status', 'PENDING')->count();

        return view('executive.index', [
            'mode' => $mode, 'from' => $from, 'to' => $to,
            'mining' => $mine, 'production' => $prod,
            'revenue' => (float) ($sales->revenue ?? 0),
            'cost' => $cost, 'fuel' => $fuel, 'fleet' => $fleet,
            'stockValue' => round((float) $stockValue, 2),
            'ar' => round($ar, 2), 'ap' => round($ap, 2),
            'criticalStock' => $criticalStock,
            'expiredPermits' => $expiredPermits,
            'hse' => $hse,
            'pendingApprovals' => $pendingApprovals,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'pits' => Pit::pluck('name', 'id')->all(),
            'products' => Item::where('type', 'PRODUCT')->pluck('name', 'id')->all(),
            'divisions' => Division::pluck('name', 'id')->all(),
        ]);
    }

    protected function resolvePeriod(string $mode, ?string $from, ?string $to): array
    {
        return match ($mode) {
            'today' => [today()->toDateString(), today()->toDateString()],
            'yesterday' => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'ytd' => [now()->startOfYear()->toDateString(), today()->toDateString()],
            'custom' => [$from ?? today()->toDateString(), $to ?? today()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), today()->toDateString()], // mtd
        };
    }
}
