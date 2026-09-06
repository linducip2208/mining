<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\Site;
use App\Models\Warehouse;
use App\Models\VendorBill;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\GoodsReceipt;
use App\Services\AuditService;
use App\Services\ApprovalService;
use App\Services\ProcurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorBillController extends Controller
{
    use AppliesDataScope;

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

        $this->ensureInScope(\App\Models\Supplier::find($validated['supplier_id']));

        try {
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
            // over-billing prevention when linked to a PO (other costs belong on PO.other_cost)
            if (!empty($validated['purchase_order_id'])) {
                $poTotal = (float) PurchaseOrder::where('id', $validated['purchase_order_id'])->value('subtotal');
                if ($subtotal > $poTotal + 0.01) {
                    throw new \DomainException('Subtotal tagihan (Rp ' . number_format($subtotal, 0, ',', '.') . ') melebihi subtotal PO (Rp ' . number_format($poTotal, 0, ',', '.') . ').');
                }
            }
            $bill->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax]);
            return $bill;
        });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

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
            ProcurementService::postVendorBill($vendor_bill);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Tagihan diposting — AP & jurnal tersimpan.');
    }

    public function void(Request $request, VendorBill $vendor_bill)
    {
        if (!auth()->user()->hasPermission('vendor_bill.void')) {
            abort(403);
        }
        $validated = $request->validate(['reason' => 'required|max:500']);
        try {
            DB::transaction(function () use ($vendor_bill, $validated) {
                if (!in_array($vendor_bill->status, ['POSTED', 'PARTIALLY_PAID'])) {
                    throw new \DomainException('Hanya tagihan POSTED yang dapat di-void.');
                }
                if ((float) $vendor_bill->paid_amount > 0) {
                    throw new \DomainException('Tagihan yang sudah dibayar (sebagian) tidak dapat di-void.');
                }
                if ($vendor_bill->journal_entry_id) {
                    $journal = \App\Models\JournalEntry::find($vendor_bill->journal_entry_id);
                    if ($journal) {
                        \App\Services\AccountingService::reverse($journal, $validated['reason']);
                    }
                }
                $vendor_bill->update(['status' => 'CANCELLED']);
                // aktual berbalik via jurnal reversal → komitmen PO dibuka kembali
                if ($vendor_bill->purchase_order_id) {
                    \App\Services\BudgetService::reopen('PURCHASE_ORDER', $vendor_bill->purchase_order_id);
                }
                AuditService::log('VOID', 'PROCUREMENT', $vendor_bill->id, VendorBill::class, null, null, $validated['reason']);
            });
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Tagihan di-void — jurnal di-reverse.');
    }

    public function pay(Request $request, VendorBill $vendor_bill)    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'reference_no' => 'nullable|max:100',
        ]);
        try {
            ProcurementService::payVendorBill($vendor_bill, (float) $validated['amount'], $validated['payment_date'], $validated['cash_account_id'] ?? null, $validated['reference_no'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Pembayaran tercatat.');
    }
}
