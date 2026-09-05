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

class StockController extends Controller
{
    public function balance(Request $request)
    {
        $query = StockLedger::query()
            ->join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_ledger.warehouse_id')
            ->when($request->warehouse_id, fn ($q) => $q->where('stock_ledger.warehouse_id', $request->warehouse_id))
            ->when($request->item_id, fn ($q) => $q->where('stock_ledger.item_id', $request->item_id))
            ->when($request->q, fn ($q) => $q->where('items.name', 'like', "%{$request->q}%")->orWhere('items.code', 'like', "%{$request->q}%"))
            ->selectRaw('stock_ledger.item_id, stock_ledger.warehouse_id, items.code, items.name, warehouses.name as warehouse,
                SUM(stock_ledger.qty_in) - SUM(stock_ledger.qty_out) as balance,
                SUM(stock_ledger.total_cost) as value')
            ->groupBy('stock_ledger.item_id', 'stock_ledger.warehouse_id', 'items.code', 'items.name', 'warehouses.name')
            ->havingRaw('balance != 0');

        $rows = $query->orderBy('items.name')->paginate(25)->withQueryString();
        $warehouses = Warehouse::pluck('name', 'id')->all();

        return view('stock.balance', compact('rows', 'warehouses'));
    }

    public function card(Request $request)
    {
        $rows = collect();
        $item = null;
        $warehouseId = $request->warehouse_id;

        if ($request->item_id) {
            $item = Item::find($request->item_id);
            $rows = StockLedger::with(['item', 'warehouse'])
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->when($request->item_id, fn ($q) => $q->where('item_id', $request->item_id))
                ->when($request->from, fn ($q) => $q->whereDate('trx_date', '>=', $request->from))
                ->when($request->to, fn ($q) => $q->whereDate('trx_date', '<=', $request->to))
                ->orderBy('trx_date')->orderBy('id')->get();

            // running balance
            $running = 0;
            foreach ($rows as $row) {
                $running += $row->qty_in - $row->qty_out;
                $row->running = $running;
            }
        }

        return view('stock.card', [
            'rows' => $rows,
            'item' => $item,
            'items' => Item::orderBy('name')->get(),
            'warehouses' => Warehouse::pluck('name', 'id')->all(),
            'selectedWarehouse' => $warehouseId,
        ]);
    }
}
