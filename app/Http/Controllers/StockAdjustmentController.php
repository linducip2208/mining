<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\ApprovalService;
use App\Services\AuditService;
use App\Services\NumberingService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    use AppliesDataScope;

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
            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            $this->ensureCompanyInScope($warehouse->company_id);
            $this->ensureSiteInScope($warehouse->site_id);
            $adjustment = StockAdjustment::create([
                'number' => NumberingService::generate('ADJ'),
                'company_id' => $warehouse->company_id,
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
        if (in_array($stock_adjustment->status, ['DRAFT', 'COUNTING', 'REVIEW'], true)) {
            // wajib lewat approval center dulu (workflow ADJ-APPROVAL)
            ApprovalService::submit('STOCK', 'STOCK_ADJUSTMENT', $stock_adjustment);

            return back()->with('success', $stock_adjustment->fresh()->status === 'SUBMITTED'
                ? 'Penyesuaian diajukan ke approval center.'
                : 'Penyesuaian disetujui — klik Posting sekali lagi.');
        }
        if ($stock_adjustment->status !== 'APPROVED') {
            return back()->with('error', 'Status tidak dapat diposting.');
        }

        try {
            DB::transaction(function () use ($stock_adjustment) {
                $varianceGain = 0;
                $varianceLoss = 0;
                foreach ($stock_adjustment->items as $line) {
                    $diff = (float) $line->diff_qty;
                    if ($diff > 0) {
                        StockService::move($stock_adjustment->warehouse_id, $line->item_id, 'ADJUSTMENT_PLUS', $diff, 0, $stock_adjustment->company_id, null, $stock_adjustment->id, 'STOCK_ADJUSTMENT', $stock_adjustment->number, null, $stock_adjustment->adjustment_date->toDateString());
                    } elseif ($diff < 0) {
                        StockService::move($stock_adjustment->warehouse_id, $line->item_id, 'ADJUSTMENT_MINUS', 0, abs($diff), $stock_adjustment->company_id, null, $stock_adjustment->id, 'STOCK_ADJUSTMENT', $stock_adjustment->number, null, $stock_adjustment->adjustment_date->toDateString());
                    }
                    if ($stock_adjustment->type === 'OPNAME' && $diff != 0) {
                        $item = Item::find($line->item_id);
                        $value = round(abs($diff) * (float) ($item?->avg_cost ?? 0), 2);
                        if ($value > 0) {
                            $invMap = $item && $item->type === 'SPAREPART' ? 'INVENTORY_SPAREPART' : 'INVENTORY_GENERAL';
                            if ($diff > 0) {
                                AccountingService::post($stock_adjustment->company_id, $stock_adjustment->adjustment_date->toDateString(), [
                                    ['code' => AccountingService::map($invMap), 'debit' => $value, 'memo' => 'Opname plus '.$stock_adjustment->number],
                                    ['code' => AccountingService::map('VARIANCE_REVENUE'), 'credit' => $value, 'memo' => 'Selisih opname '.$stock_adjustment->number],
                                ], 'STOCK_OPNAME', $stock_adjustment->id, $stock_adjustment->number, 'Opname '.$stock_adjustment->number, 'ADJ');
                                $varianceGain += $value;
                            } else {
                                AccountingService::post($stock_adjustment->company_id, $stock_adjustment->adjustment_date->toDateString(), [
                                    ['code' => AccountingService::map('VARIANCE_EXPENSE'), 'debit' => $value, 'memo' => 'Selisih opname '.$stock_adjustment->number],
                                    ['code' => AccountingService::map($invMap), 'credit' => $value, 'memo' => 'Opname minus '.$stock_adjustment->number],
                                ], 'STOCK_OPNAME', $stock_adjustment->id, $stock_adjustment->number, 'Opname '.$stock_adjustment->number, 'ADJ');
                                $varianceLoss += $value;
                            }
                        }
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
