<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Site;
use App\Models\Warehouse;
use App\Models\VendorBill;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\GoodsReceipt;
use App\Services\AuditService;
use App\Services\ApprovalService;
use App\Services\OperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $items = GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('procurement.grn.index', ['items' => $items, 'gr' => null, 'statuses' => ['DRAFT', 'POSTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('procurement.grn.form', [
            'gr' => null,
            'pos' => PurchaseOrder::where('status', 'APPROVED')->with('items.item')->get(),
            'warehouses' => Warehouse::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'required|date',
            'qc_status' => 'required|in:PENDING,PASSED,FAILED',
            'notes' => 'nullable|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty_received' => 'required|numeric|min:0',
            'lines.*.qty_accepted' => 'required|numeric|min:0',
        ]);

        $po = PurchaseOrder::with('items')->find($validated['purchase_order_id']);
        $unitPrices = $po->items->keyBy('item_id');

        $gr = DB::transaction(function () use ($validated, $po, $unitPrices) {
            $gr = GoodsReceipt::create([
                'number' => \App\Services\NumberingService::generate('GRN', $po->company_id),
                'purchase_order_id' => $validated['purchase_order_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'receipt_date' => $validated['receipt_date'],
                'qc_status' => $validated['qc_status'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['lines'] as $line) {
                if ($line['qty_received'] > 0) {
                    $gr->items()->create([
                        'item_id' => $line['item_id'],
                        'qty_received' => $line['qty_received'],
                        'qty_accepted' => $line['qty_accepted'],
                        'unit_cost' => $unitPrices[$line['item_id']]->unit_price ?? 0,
                    ]);
                }
            }
            return $gr;
        });

        AuditService::created('PROCUREMENT', $gr);
        return redirect()->route('goods-receipts.index')->with('success', 'Penerimaan barang dibuat.');
    }

    public function show(GoodsReceipt $goods_receipt)
    {
        return view('procurement.grn.index', ['gr' => $goods_receipt->load(['items.item', 'purchaseOrder.supplier']), 'items' => GoodsReceipt::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'POSTED', 'CANCELLED']]);
    }

    public function post(GoodsReceipt $goods_receipt)
    {
        try {
            OperationsService::postGoodsReceipt($goods_receipt);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'GRN diposting — stok bertambah.');
    }
}
