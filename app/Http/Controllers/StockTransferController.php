<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\NumberingService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $items = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when(! is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($q) => $q->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('stock.transfer.index', ['items' => $items, 'transfer' => null, 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('stock.transfer.form', [
            'transfer' => null,
            'warehouses' => Warehouse::pluck('name', 'id')->all(),
            'items' => Item::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|different:to_warehouse_id|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty' => 'required|numeric|min:0.0001',
        ]);

        $transfer = DB::transaction(function () use ($validated) {
            $transfer = StockTransfer::create([
                'number' => NumberingService::generate('TRF'),
                'company_id' => auth()->user()->accessibleCompanyIds()[0] ?? Company::value('id'),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['lines'] as $line) {
                $transfer->items()->create(['item_id' => $line['item_id'], 'qty' => $line['qty']]);
            }

            return $transfer;
        });

        AuditService::created('STOCK', $transfer);

        return redirect()->route('stock-transfers.index')->with('success', 'Transfer dibuat.');
    }

    public function show(StockTransfer $stock_transfer)
    {
        return view('stock.transfer.index', ['transfer' => $stock_transfer->load(['items.item', 'fromWarehouse', 'toWarehouse']), 'items' => StockTransfer::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'CANCELLED']]);
    }

    public function post(StockTransfer $stock_transfer)
    {
        if (! in_array($stock_transfer->status, ['DRAFT', 'APPROVED'])) {
            return back()->with('error', 'Status tidak dapat diposting.');
        }

        try {
            DB::transaction(function () use ($stock_transfer) {
                foreach ($stock_transfer->items as $line) {
                    StockService::move($stock_transfer->from_warehouse_id, $line->item_id, 'TRANSFER_OUT', 0, (float) $line->qty, $stock_transfer->company_id, null, $stock_transfer->id, 'STOCK_TRANSFER', $stock_transfer->number, null, $stock_transfer->transfer_date->toDateString());
                    StockService::move($stock_transfer->to_warehouse_id, $line->item_id, 'TRANSFER_IN', (float) $line->qty, 0, $stock_transfer->company_id, null, $stock_transfer->id, 'STOCK_TRANSFER', $stock_transfer->number, null, $stock_transfer->transfer_date->toDateString());
                }
                $stock_transfer->update(['status' => 'POSTED', 'posted_by' => auth()->id(), 'posted_at' => now()]);
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditService::log('POST', 'STOCK', $stock_transfer->id, StockTransfer::class, null, ['number' => $stock_transfer->number]);

        return back()->with('success', 'Transfer diposting.');
    }
}
