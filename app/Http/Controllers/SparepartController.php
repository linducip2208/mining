<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\MaintenancePart;
use App\Models\PurchaseRequest;
use App\Models\SparepartCompatibility;
use App\Models\StockAdjustment;
use App\Models\StockLedger;
use App\Models\StockReservation;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\MaintenanceService;
use App\Services\NumberingService;
use App\Services\SparepartService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sparepart warehouse administration. All balances come from the stock
 * ledger — this controller never stores a parallel stock balance.
 */
class SparepartController extends Controller
{
    use AppliesDataScope, ExportsCsv;

    // ============ DASHBOARD ============

    public function dashboard(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $items = SparepartService::spareparts()->with(['category', 'unit'])->get();
        $rows = [];
        $critical = 0;
        $outOfStock = 0;
        $value = 0;
        foreach ($items as $item) {
            $whs = $warehouseId ? [$warehouseId] : Warehouse::pluck('id');
            $avail = 0;
            $onHand = 0;
            foreach ($whs as $wh) {
                $onHand += SparepartService::onHand($wh, $item->id);
                $avail += SparepartService::available($wh, $item->id);
            }
            $value += $onHand * (float) $item->avg_cost;
            $status = SparepartService::stockStatus($item, $avail);
            if ($status === 'CRITICAL') {
                $critical++;
            }
            if ($status === 'OUT_OF_STOCK') {
                $outOfStock++;
            }
            $rows[] = compact('item', 'avail', 'onHand', 'status');
        }
        $today = now()->toDateString();
        $inToday = StockLedger::whereDate('trx_date', $today)->where('qty_in', '>', 0)->whereHas('item', fn ($q) => $q->where('type', 'SPAREPART'))->sum('qty_in');
        $outToday = StockLedger::whereDate('trx_date', $today)->where('qty_out', '>', 0)->whereHas('item', fn ($q) => $q->where('type', 'SPAREPART'))->sum('qty_out');
        $woWaiting = MaintenancePart::where('issue_status', 'PENDING')->count();
        $opnameOpen = StockAdjustment::where('type', 'OPNAME')->whereNotIn('status', ['POSTED', 'CANCELLED'])->count();
        $usage = StockLedger::selectRaw('item_id, SUM(qty_out) total')->where('qty_out', '>', 0)
            ->whereHas('item', fn ($q) => $q->where('type', 'SPAREPART'))
            ->whereDate('trx_date', '>=', now()->subMonths(6))->groupBy('item_id')->orderByDesc('total')->limit(10)
            ->with('item')->get();

        return view('sparepart.dashboard', compact('rows', 'critical', 'outOfStock', 'value', 'inToday', 'outToday', 'woWaiting', 'opnameOpen', 'usage')
            + ['warehouses' => Warehouse::orderBy('name')->get(), 'warehouseId' => $warehouseId]);
    }

    // ============ MASTER ============

    public function master(Request $request)
    {
        $items = SparepartService::spareparts()->with(['category', 'unit', 'storageLocation'])
            ->when($request->q, fn ($q) => $q->where(fn ($w) => $w->where('code', 'like', "%{$request->q}%")->orWhere('name', 'like', "%{$request->q}%")->orWhere('part_number', 'like', "%{$request->q}%")))
            ->when($request->category_id, fn ($q) => $q->where('item_category_id', $request->category_id))
            ->orderBy('code')->paginate(20)->withQueryString();

        return view('sparepart.master', [
            'items' => $items, 'item' => null,
            'categories' => ItemCategory::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
            'locations' => StorageLocation::with('warehouse')->orderBy('code')->limit(200)->get(),
            'suppliers' => Supplier::orderBy('name')->limit(100)->get(),
        ]);
    }

    public function storeMaster(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|max:30|unique:items,code',
            'name' => 'required|max:150',
            'item_category_id' => 'required|exists:item_categories,id',
            'unit_id' => 'required|exists:units,id',
            'brand' => 'nullable|max:100',
            'part_number' => 'nullable|max:100',
            'alt_part_number' => 'nullable|max:100',
            'storage_location_id' => 'nullable|exists:storage_locations,id',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
            'standard_cost' => 'nullable|numeric|min:0',
        ]);
        $item = Item::create($validated + ['type' => 'SPAREPART', 'status' => true, 'created_by' => auth()->id()]);
        AuditService::created('SPAREPART', $item);

        return back()->with('success', 'Sparepart dibuat: '.$item->code);
    }

    public function updateMaster(Request $request, Item $item)
    {
        abort_unless($item->type === 'SPAREPART', 404);
        $validated = $request->validate([
            'name' => 'required|max:150',
            'item_category_id' => 'required|exists:item_categories,id',
            'unit_id' => 'required|exists:units,id',
            'brand' => 'nullable|max:100',
            'part_number' => 'nullable|max:100',
            'alt_part_number' => 'nullable|max:100',
            'storage_location_id' => 'nullable|exists:storage_locations,id',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
            'standard_cost' => 'nullable|numeric|min:0',
            'status' => 'boolean',
        ]);
        $item->update($validated + ['updated_by' => auth()->id()]);
        AuditService::updated('SPAREPART', $item);

        return back()->with('success', 'Sparepart diperbarui.');
    }

    /**
     * Scanner-friendly lookup (barcode/QR scanners act as keyboard input).
     */
    public function scan(Request $request)
    {
        $code = trim((string) $request->get('code', ''));
        $item = $code !== '' ? SparepartService::spareparts()->where(fn ($q) => $q->where('code', $code)->orWhere('part_number', $code))->with(['unit', 'storageLocation'])->first() : null;
        if (! $item) {
            return back()->with('error', 'Sparepart tidak ditemukan: '.$code);
        }

        return redirect()->route('sparepart.card', ['item_id' => $item->id] + ($request->warehouse_id ? ['warehouse_id' => $request->warehouse_id] : []));
    }

    // ============ BARANG MASUK ============

    public function receiptForm()
    {
        return view('sparepart.movement', [
            'mode' => 'in',
            'items' => SparepartService::spareparts()->orderBy('code')->limit(500)->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->limit(200)->get(),
            'movements' => $this->recentMovements('in'),
        ]);
    }

    public function storeReceipt(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'qty' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'condition' => 'required|in:BAIK,RUSAK,REPAIR,REJECTED',
            'receipt_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference_no' => 'nullable|max:100',
            'notes' => 'nullable',
            'is_opening' => 'boolean',
        ]);
        $item = Item::findOrFail($validated['item_id']);
        abort_unless($item->type === 'SPAREPART', 422, 'Bukan sparepart.');
        if ($validated['condition'] === 'REJECTED') {
            return back()->with('error', 'Kondisi REJECTED tidak masuk stok.');
        }
        $this->ensureCompanyInScope(Warehouse::find($validated['warehouse_id'])?->company_id);

        try {
            $ledger = StockService::move(
                $validated['warehouse_id'], $item->id,
                $request->boolean('is_opening') ? 'OPENING' : 'SPAREPART_IN',
                (float) $validated['qty'], 0,
                Warehouse::find($validated['warehouse_id'])->company_id, null, null,
                $request->boolean('is_opening') ? 'OPENING_BALANCE' : 'SPAREPART_RECEIPT',
                $validated['reference_no'] ?? ('SPR-IN-'.now()->format('YmdHis')),
                $validated['unit_cost'] ?? null, $validated['receipt_date'],
                trim(($validated['condition'] !== 'BAIK' ? "[{$validated['condition']}] " : '').($validated['notes'] ?? ''))
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        if ($request->boolean('is_opening')) {
            $ledger->update(['source' => 'LEGACY_IMPORT', 'import_batch_id' => $validated['reference_no'] ?? null]);
        }
        AuditService::log('CREATE', 'SPAREPART', $ledger->id, StockLedger::class, null, ['item' => $item->code, 'qty' => $validated['qty']]);

        return back()->with('success', 'Barang masuk dicatat ke stock ledger.');
    }

    // ============ BARANG KELUAR ============

    public function issueForm()
    {
        return view('sparepart.movement', [
            'mode' => 'out',
            'items' => SparepartService::spareparts()->orderBy('code')->limit(500)->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'workOrders' => WorkOrder::whereNotIn('status', ['COMPLETED', 'CLOSED', 'CANCELLED'])->orderByDesc('id')->limit(100)->get(),
            'equipment' => Equipment::orderBy('code')->limit(200)->get(),
            'movements' => $this->recentMovements('out'),
        ]);
    }

    public function storeIssue(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'qty' => 'required|numeric|min:0.0001',
            'issue_date' => 'required|date',
            'reason' => 'required|in:MAINTENANCE,WORK_ORDER,TRANSFER,CONSUMPTION,RETURN_TO_VENDOR,ADJUSTMENT,OTHER',
            'work_order_id' => 'nullable|exists:work_orders,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'received_by' => 'nullable|max:150',
            'notes' => 'nullable',
        ]);
        $item = Item::findOrFail($validated['item_id']);
        abort_unless($item->type === 'SPAREPART', 422, 'Bukan sparepart.');
        $this->ensureCompanyInScope(Warehouse::find($validated['warehouse_id'])?->company_id);

        try {
            if ($validated['work_order_id'] ?? null) {
                $wo = WorkOrder::findOrFail($validated['work_order_id']);
                $part = MaintenancePart::create([
                    'work_order_id' => $wo->id,
                    'item_id' => $item->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'qty' => $validated['qty'],
                    'unit_cost' => (float) $item->avg_cost,
                    'issue_status' => 'PENDING',
                ]);
                MaintenanceService::issuePart($part->fresh());
                AuditService::log('UPDATE', 'SPAREPART', $part->id, MaintenancePart::class, null, ['event' => 'issued_to_wo', 'wo' => $wo->number]);
            } else {
                StockService::move(
                    $validated['warehouse_id'], $item->id, 'SPAREPART_OUT', 0, (float) $validated['qty'],
                    Warehouse::find($validated['warehouse_id'])->company_id, null, null, 'SPAREPART_ISSUE',
                    'SPR-OUT-'.now()->format('YmdHis'), null, $validated['issue_date'],
                    trim("[{$validated['reason']}] " . (($validated['equipment_id'] ?? null) ? 'EQ:' . $validated['equipment_id'] . ' ' : '') . (($validated['received_by'] ?? null) ? "Diterima: {$validated['received_by']} " : '') . ($validated['notes'] ?? ''))
                );
                AuditService::log('CREATE', 'SPAREPART', $item->id, Item::class, null, ['event' => 'issued', 'qty' => $validated['qty']]);
            }
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Barang keluar dicatat ke stock ledger.');
    }

    protected function recentMovements(string $dir)
    {
        return StockLedger::with(['item', 'warehouse'])
            ->whereHas('item', fn ($q) => $q->where('type', 'SPAREPART'))
            ->when($dir === 'in', fn ($q) => $q->where('qty_in', '>', 0), fn ($q) => $q->where('qty_out', '>', 0))
            ->orderByDesc('id')->limit(15)->get();
    }

    // ============ RESERVATION & RETURN ============

    public function reserve(Request $request)
    {
        $validated = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id',
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'qty' => 'required|numeric|min:0.0001',
        ]);
        try {
            $res = SparepartService::reserve($validated['warehouse_id'], $validated['item_id'], WorkOrder::findOrFail($validated['work_order_id']), (float) $validated['qty']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditService::log('CREATE', 'SPAREPART', $res->id, StockReservation::class, null, ['event' => 'reserved']);

        return back()->with('success', 'Sparepart direservasi untuk WO.');
    }

    public function returnStock(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:stock_reservations,id',
            'qty' => 'required|numeric|min:0.0001',
        ]);
        $res = StockReservation::findOrFail($validated['reservation_id']);
        if ($res->status !== 'RESERVED' || (float) $res->issued_qty < (float) $validated['qty']) {
            return back()->with('error', 'Return melebihi qty yang sudah di-issue.');
        }
        $wh = Warehouse::findOrFail($res->warehouse_id);
        StockService::move($res->warehouse_id, $res->item_id, 'RETURN', (float) $validated['qty'], 0, $wh->company_id, null, $res->id, 'SPAREPART_RETURN', $res->ref_number);
        $res->increment('returned_qty', (float) $validated['qty']);
        AuditService::log('UPDATE', 'SPAREPART', $res->id, StockReservation::class, null, ['event' => 'returned', 'qty' => $validated['qty']]);

        return back()->with('success', 'Return dicatat sebagai stock masuk ber-referensi.');
    }

    // ============ KARTU STOK ============

    public function card(Request $request)
    {
        $query = StockLedger::with(['warehouse', 'item.unit'])
            ->whereHas('item', fn ($q) => $q->where('type', 'SPAREPART'))
            ->when($request->item_id, fn ($q) => $q->where('item_id', $request->item_id))
            ->when($request->warehouse_id, fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->from, fn ($q) => $q->whereDate('trx_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('trx_date', '<=', $request->to))
            ->orderBy('trx_date')->orderBy('id');
        $lines = (clone $query)->limit(500)->get();
        $balance = 0;
        $value = 0;
        $rows = [];
        foreach ($lines as $line) {
            $balance += (float) $line->qty_in - (float) $line->qty_out;
            $value += (float) $line->total_cost * ((float) $line->qty_in > 0 ? 1 : -1);
            $rows[] = compact('line', 'balance', 'value');
        }

        return view('sparepart.card', [
            'rows' => $rows,
            'items' => SparepartService::spareparts()->orderBy('code')->limit(500)->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    // ============ OPNAME ============

    public function opnameForm()
    {
        return view('sparepart.opname', [
            'items' => SparepartService::spareparts()->orderBy('code')->limit(500)->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'adjustments' => StockAdjustment::where('type', 'OPNAME')->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function storeOpname(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_date' => 'required|date',
            'reason' => 'nullable|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.counted_qty' => 'required|numeric|min:0',
        ]);
        $wh = Warehouse::findOrFail($validated['warehouse_id']);
        $this->ensureCompanyInScope($wh->company_id);

        $adj = DB::transaction(function () use ($validated, $wh) {
            $adj = StockAdjustment::create([
                'number' => NumberingService::generate('ADJ', $wh->company_id),
                'type' => 'OPNAME',
                'warehouse_id' => $wh->id,
                'company_id' => $wh->company_id,
                'adjustment_date' => $validated['adjustment_date'],
                'reason' => $validated['reason'] ?? 'Stock opname sparepart',
                'status' => 'COUNTING',
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['lines'] as $line) {
                $system = SparepartService::onHand($wh->id, (int) $line['item_id']);
                $adj->items()->create([
                    'item_id' => $line['item_id'],
                    'system_qty' => $system,
                    'counted_qty' => $line['counted_qty'],
                    'diff_qty' => (float) $line['counted_qty'] - $system,
                ]);
            }

            return $adj;
        });
        AuditService::created('STOCK', $adj);

        return back()->with('success', 'Opname dibuat (COUNTING): '.$adj->number);
    }

    public function opnameReview(StockAdjustment $adjustment)
    {
        abort_unless($adjustment->type === 'OPNAME' && $adjustment->status === 'COUNTING', 422);
        $adjustment->update(['status' => 'REVIEW']);
        AuditService::log('UPDATE', 'STOCK', $adjustment->id, StockAdjustment::class, null, ['event' => 'opname_reviewed']);

        return back()->with('success', 'Opname masuk REVIEW.');
    }

    // ============ REPORTS & RECOMMENDATION ============

    public function reports(Request $request)
    {
        $type = $request->get('type', 'balance');
        $warehouseId = $request->warehouse_id;
        $data = match ($type) {
            'movement' => $this->reportMovement($warehouseId, $request),
            'valuation' => $this->reportValuation($warehouseId),
            'usage' => $this->reportUsage($request),
            'minstock' => $this->reportMinStock($warehouseId),
            default => $this->reportBalance($warehouseId),
        };
        if ($request->get('export') === 'csv') {
            return $this->exportCsv('sparepart-'.$type, $data['headers'], $data['rows']);
        }

        return view('sparepart.reports', [
            'type' => $type, 'headers' => $data['headers'], 'rows' => $data['rows'],
            'warehouses' => Warehouse::orderBy('name')->get(), 'warehouseId' => $warehouseId,
        ]);
    }

    protected function sparepartRows(?int $warehouseId): array
    {
        $out = [];
        foreach (SparepartService::spareparts()->with(['category', 'unit'])->get() as $item) {
            $whs = $warehouseId ? [$warehouseId] : Warehouse::pluck('id')->all();
            $onHand = $avail = 0;
            foreach ($whs as $wh) {
                $onHand += SparepartService::onHand($wh, $item->id);
                $avail += SparepartService::available($wh, $item->id);
            }
            $out[] = compact('item', 'onHand', 'avail');
        }

        return $out;
    }

    protected function reportBalance(?int $warehouseId): array
    {
        $rows = [];
        foreach ($this->sparepartRows($warehouseId) as $r) {
            $rows[] = [$r['item']->code, $r['item']->name, $r['onHand'], $r['avail'], $r['item']->unit?->code, number_format($r['onHand'] * (float) $r['item']->avg_cost, 2)];
        }

        return ['headers' => ['Kode', 'Nama', 'On Hand', 'Available', 'Satuan', 'Nilai'], 'rows' => $rows];
    }

    protected function reportValuation(?int $warehouseId): array
    {
        return $this->reportBalance($warehouseId);
    }

    protected function reportMovement(?int $warehouseId, Request $request): array
    {
        $rows = [];
        $lines = StockLedger::with(['item', 'warehouse'])->whereHas('item', fn ($q) => $q->where('type', 'SPAREPART'))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($request->from, fn ($q) => $q->whereDate('trx_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('trx_date', '<=', $request->to))
            ->orderByDesc('id')->limit(1000)->get();
        foreach ($lines as $l) {
            $rows[] = [$l->trx_date, $l->ref_number, $l->movement_type, $l->item->code, $l->qty_in, $l->qty_out, $l->warehouse->code ?? ''];
        }

        return ['headers' => ['Tanggal', 'Referensi', 'Tipe', 'Sparepart', 'Masuk', 'Keluar', 'Gudang'], 'rows' => $rows];
    }

    protected function reportUsage(Request $request): array
    {
        $rows = [];
        $usage = StockLedger::selectRaw('item_id, SUM(qty_out) total, SUM(total_cost) cost')->where('qty_out', '>', 0)
            ->whereHas('item', fn ($q) => $q->where('type', 'SPAREPART'))
            ->when($request->from, fn ($q) => $q->whereDate('trx_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('trx_date', '<=', $request->to))
            ->groupBy('item_id')->orderByDesc('total')->limit(200)->with('item')->get();
        foreach ($usage as $u) {
            $rows[] = [$u->item->code, $u->item->name, $u->total, number_format($u->cost, 2)];
        }

        return ['headers' => ['Kode', 'Nama', 'Qty Keluar', 'Nilai'], 'rows' => $rows];
    }

    protected function reportMinStock(?int $warehouseId): array
    {
        $rows = [];
        foreach ($this->sparepartRows($warehouseId) as $r) {
            $status = SparepartService::stockStatus($r['item'], $r['avail']);
            if ($status !== 'NORMAL') {
                $rows[] = [$r['item']->code, $r['item']->name, $r['avail'], $r['item']->min_stock, $r['item']->reorder_point, $status];
            }
        }

        return ['headers' => ['Kode', 'Nama', 'Available', 'Min', 'Reorder', 'Status'], 'rows' => $rows];
    }

    public function recommend(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $rows = [];
        foreach ($this->sparepartRows($warehouseId) as $r) {
            $status = SparepartService::stockStatus($r['item'], $r['avail']);
            if (in_array($status, ['LOW', 'CRITICAL', 'OUT_OF_STOCK'], true)) {
                $rows[] = $r + ['recommended' => SparepartService::recommendedQty($r['item'], $r['avail']), 'status' => $status];
            }
        }

        return view('sparepart.recommend', [
            'rows' => $rows,
            'warehouses' => Warehouse::orderBy('name')->get(), 'warehouseId' => $warehouseId,
        ]);
    }

    public function recommendToPR(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'company_id' => 'required|exists:companies,id',
        ]);
        $this->ensureCompanyInScope($validated['company_id']);
        $pr = PurchaseRequest::create([
            'number' => NumberingService::generate('PR', $validated['company_id']),
            'company_id' => $validated['company_id'],
            'request_date' => now()->toDateString(),
            'status' => 'DRAFT',
            'notes' => 'Dari rekomendasi pembelian sparepart',
            'created_by' => auth()->id(),
        ]);
        foreach ($validated['items'] as $line) {
            $pr->items()->create(['item_id' => $line['item_id'], 'qty' => $line['qty']]);
        }
        AuditService::created('PROCUREMENT', $pr);

        return redirect()->route('purchase-requests.show', $pr)->with('success', 'PR dibuat dari rekomendasi: '.$pr->number);
    }

    // ============ COMPATIBILITY & LOCATIONS ============

    public function compatibility()
    {
        return view('sparepart.compatibility', [
            'rows' => SparepartCompatibility::with('item')->orderByDesc('id')->paginate(20),
            'items' => SparepartService::spareparts()->orderBy('code')->limit(500)->get(),
        ]);
    }

    public function storeCompatibility(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'equipment_make' => 'nullable|max:100',
            'equipment_model' => 'nullable|max:100',
            'part_number' => 'nullable|max:100',
            'note' => 'nullable|max:255',
        ]);
        SparepartCompatibility::create($validated + ['created_by' => auth()->id()]);
        AuditService::log('CREATE', 'SPAREPART', $validated['item_id'], Item::class, null, ['event' => 'compatibility_added']);

        return back()->with('success', 'Kompatibilitas ditambahkan (opsional, tidak wajib).');
    }

    public function locations(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'zone' => 'required|max:20',
                'rack' => 'required|max:20',
                'bin' => 'required|max:20',
                'name' => 'nullable|max:150',
                'capacity' => 'nullable|numeric|min:0',
            ]);
            StorageLocation::create($validated + [
                'code' => "{$validated['zone']}-{$validated['rack']}-{$validated['bin']}",
                'created_by' => auth()->id(),
            ]);

            return back()->with('success', 'Lokasi penyimpanan dibuat.');
        }

        return view('sparepart.locations', [
            'rows' => StorageLocation::with('warehouse')->orderBy('code')->paginate(20),
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }
}
