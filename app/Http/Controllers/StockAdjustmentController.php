<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Item;
use App\Models\Site;
use App\Models\Warehouse;
use App\Models\StockLedger;
use App\Models\StockTransfer;
use App\Models\StockAdjustment;
use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $items = StockAdjustment::with(['warehouse', 'items'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('stock.adjustment.index', ['items' => $items, 'adjustment' => null, 'statuses' => ['DRAFT', 'APPROVED', 'POSTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('stock.adjustment.form', [
            'adjustment' => null,
            'warehouses' => Warehouse::pluck('name', 'id')->all(),
            'items' => Item::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:ADJUSTMENT,OPNAME',
            'reason' => 'required|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.counted_qty' => 'required|numeric|min:0',
        ]);

        $adjustment = DB::transaction(function () use ($validated) {
            $adjustment = StockAdjustment::create([
                'number' => \App\Services\NumberingService::generate('ADJ'),
                'company_id' => auth()->user()->accessibleCompanyIds()[0] ?? Company::value('id'),
                'warehouse_id' => $validated['warehouse_id'],
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['lines'] as $line) {
                $system = StockService::balance($validated['warehouse_id'], $line['item_id']);
                $diff = round((float) $line['counted_qty'] - $system, 4);
                $adjustment->items()->create([
                    'item_id' => $line['item_id'],
                    'system_qty' => $system,
                    'counted_qty' => $line['counted_qty'],
                    'diff_qty' => $diff,
                ]);
            }
            return $adjustment;
        });

        AuditService::created('STOCK', $adjustment);
        return redirect()->route('stock-adjustments.index')->with('success', 'Penyesuaian dibuat.');
    }

    public function show(StockAdjustment $stock_adjustment)
    {
        return view('stock.adjustment.index', ['adjustment' => $stock_adjustment->load(['items.item', 'warehouse']), 'items' => StockAdjustment::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'APPROVED', 'POSTED', 'CANCELLED']]);
    }

    public function post(StockAdjustment $stock_adjustment)
    {
        if ($stock_adjustment->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak dapat diposting.');
        }

        try {
            DB::transaction(function () use ($stock_adjustment) {
                foreach ($stock_adjustment->items as $line) {
                    $diff = (float) $line->diff_qty;
                    if ($diff > 0) {
                        StockService::move($stock_adjustment->warehouse_id, $line->item_id, 'ADJUSTMENT_PLUS', $diff, 0, $stock_adjustment->company_id, null, $stock_adjustment->id, 'STOCK_ADJUSTMENT', $stock_adjustment->number, null, $stock_adjustment->adjustment_date->toDateString());
                    } elseif ($diff < 0) {
                        StockService::move($stock_adjustment->warehouse_id, $line->item_id, 'ADJUSTMENT_MINUS', 0, abs($diff), $stock_adjustment->company_id, null, $stock_adjustment->id, 'STOCK_ADJUSTMENT', $stock_adjustment->number, null, $stock_adjustment->adjustment_date->toDateString());
                    }
                }
                $stock_adjustment->update(['status' => 'POSTED', 'posted_by' => auth()->id(), 'posted_at' => now()]);
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditService::log('POST', 'STOCK', $stock_adjustment->id, StockAdjustment::class, null, ['number' => $stock_adjustment->number]);
        return back()->with('success', 'Penyesuaian diposting.');
    }
}
