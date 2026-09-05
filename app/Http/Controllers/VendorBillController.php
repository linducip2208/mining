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

class VendorBillController extends Controller
{
    public function index(Request $request)
    {
        $items = VendorBill::with(['supplier', 'purchaseOrder'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('procurement.bill.index', ['items' => $items, 'bill' => null, 'statuses' => ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED']]);
    }

    public function create()
    {
        return view('procurement.bill.form', [
            'bill' => null,
            'suppliers' => \App\Models\Supplier::where('status', true)->get(),
            'pos' => PurchaseOrder::where('status', 'APPROVED')->get(),
            'grs' => GoodsReceipt::where('status', 'POSTED')->get(),
            'items' => \App\Models\Item::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'goods_receipt_id' => 'nullable|exists:goods_receipts,id',
            'supplier_invoice_no' => 'nullable|max:100',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:bill_date',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.description' => 'nullable|max:255',
            'lines.*.qty' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
        ]);

        $bill = DB::transaction(function () use ($validated, $request) {
            $subtotal = 0;
            $bill = VendorBill::create([
                'number' => \App\Services\NumberingService::generate('BILL'),
                'supplier_id' => $validated['supplier_id'],
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'goods_receipt_id' => $validated['goods_receipt_id'] ?? null,
                'supplier_invoice_no' => $validated['supplier_invoice_no'] ?? null,
                'bill_date' => $validated['bill_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['lines'] as $line) {
                $total = round((float) $line['qty'] * (float) $line['unit_price'], 2);
                $subtotal += $total;
                $bill->items()->create([
                    'item_id' => $line['item_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'total_price' => $total,
                ]);
            }
            $tax = round($subtotal * (float) \App\Models\Setting::get('tax.default_purchase_tax_rate', 11) / 100, 2);
            $bill->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax]);
            return $bill;
        });

        AuditService::created('PROCUREMENT', $bill);
        return redirect()->route('vendor-bills.index')->with('success', 'Tagihan vendor dibuat.');
    }

    public function show(VendorBill $vendor_bill)
    {
        return view('procurement.bill.index', ['bill' => $vendor_bill->load(['items', 'supplier']), 'items' => VendorBill::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED']]);
    }

    public function post(VendorBill $vendor_bill)
    {
        try {
            OperationsService::postVendorBill($vendor_bill);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Tagihan diposting — AP & jurnal tersimpan.');
    }

    public function pay(Request $request, VendorBill $vendor_bill)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'reference_no' => 'nullable|max:100',
        ]);
        try {
            OperationsService::payVendorBill($vendor_bill, (float) $validated['amount'], $validated['payment_date'], $validated['cash_account_id'] ?? null, $validated['reference_no'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Pembayaran tercatat.');
    }
}
