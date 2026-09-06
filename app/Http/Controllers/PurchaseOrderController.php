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

class PurchaseOrderController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = PurchaseOrder::with(['supplier', 'items', 'company'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))

            ->when(!is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('procurement.po.index', ['items' => $items, 'po' => null, 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'PARTIALLY_RECEIVED', 'COMPLETED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('procurement.po.form', [
            'po' => null,
            'suppliers' => \App\Models\Supplier::where('status', true)->get(),
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'items' => \App\Models\Item::orderBy('name')->get(),
            'paymentTerms' => \App\Models\PaymentTerm::all(),
            'prs' => PurchaseRequest::where('status', 'APPROVED')->get(),
            'taxCode' => (float) \App\Models\Setting::get('tax.default_sales_tax_rate', 11),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        try {
            $po = DB::transaction(function () use ($validated, $request) {
                $po = PurchaseOrder::create($validated + [
                    'number' => \App\Services\NumberingService::generate('PO', $validated['company_id']),
                    'status' => 'DRAFT',
                    'created_by' => auth()->id(),
                ]);
                $this->syncLines($po, $request);
                return $po;
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        AuditService::created('PROCUREMENT', $po);
        return redirect()->route('purchase-orders.index')->with('success', 'PO dibuat.');
    }

    public function show(PurchaseOrder $purchase_order)
    {
        return view('procurement.po.index', ['po' => $purchase_order->load(['items.item', 'supplier']), 'items' => PurchaseOrder::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'PARTIALLY_RECEIVED', 'COMPLETED', 'CANCELLED']]);
    }

    public function cancel(PurchaseOrder $purchase_order)
    {
        if (!in_array($purchase_order->status, ['DRAFT', 'SUBMITTED'])) {
            return back()->with('error', 'Hanya DRAFT/SUBMITTED yang dapat dibatalkan.');
        }
        $purchase_order->update(['status' => 'CANCELLED']);
        \App\Services\BudgetService::release('PURCHASE_ORDER', $purchase_order->id);
        \App\Services\AuditService::log('CANCEL', 'PROCUREMENT', $purchase_order->id, PurchaseOrder::class);
        return back()->with('success', 'PO dibatalkan — komitmen budget dilepas.');
    }

    public function approve(PurchaseOrder $purchase_order)    {
        if (!auth()->user()->hasPermission('purchase_order.approve')) {
            abort(403);
        }
        $previous = $purchase_order->status;
        $purchase_order->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'PROCUREMENT', $purchase_order->id, PurchaseOrder::class);
        try {
            $warn = \App\Services\BudgetService::commitPurchaseOrder($purchase_order->fresh());
        } catch (\DomainException $e) {
            // block-mode: batalkan approval bila budget menolak
            $purchase_order->update(['status' => $previous, 'approved_by' => null]);
            return back()->with('error', 'PO tidak dapat disetujui: ' . $e->getMessage());
        }
        return back()->with('success', 'PO disetujui.' . ($warn ? ' Peringatan budget: ' . $warn : ''));
    }

    protected function syncLines(PurchaseOrder $po, Request $request): void
    {
        $po->items()->delete();
        $subtotal = 0;
        foreach ($request->input('lines', []) as $line) {
            if (!empty($line['item_id']) && $line['qty'] > 0) {
                $total = round((float) $line['qty'] * (float) $line['unit_price'], 2);
                // contract guard: block over-contract lines without override permission
                \App\Services\ContractService::assertPurchaseWithinContract(
                    $po->supplier_id, (int) $line['item_id'], (float) $line['qty'], $total, $po->id,
                    $po->order_date?->toDateString()
                );
                $subtotal += $total;
                $po->items()->create(['item_id' => $line['item_id'], 'qty' => $line['qty'], 'unit_price' => $line['unit_price'], 'total_price' => $total, 'remark' => $line['remark'] ?? null]);
            }
        }
        $tax = round($subtotal * (float) \App\Models\Setting::get('tax.default_sales_tax_rate', 11) / 100, 2);
        $po->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax + (float) ($request->other_cost ?? 0)]);
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_request_id' => 'nullable|exists:purchase_requests,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'other_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|max:2000',
        ]);
    }
}
