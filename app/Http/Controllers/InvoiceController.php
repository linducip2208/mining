<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\Customer;
use App\Services\AuditService;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Invoice::with(['customer', 'items'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->overdue, fn ($q) => $q->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->whereDate('due_date', '<', now()))

            ->when(!is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('sales.invoice.index', ['items' => $items, 'invoice' => null, 'statuses' => ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED', 'VOID']]);
    }

    public function create()
    {
        return view('sales.invoice.create', [
            'salesOrders' => SalesOrder::where('status', 'PARTIALLY_DELIVERED')->with(['customer', 'items'])->get(),
            'customers' => Customer::where('status', true)->get(),
        ]);
    }

    /**
     * Generate invoice from SO (delivered qty only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'use_deposit' => 'boolean',
        ]);

        $so = SalesOrder::with('items')->find($validated['sales_order_id']);
        $this->ensureInScope($so);

        try {
            $invoice = SalesService::createInvoice($so, \Carbon\Carbon::parse($validated['invoice_date']), null, $request->boolean('use_deposit'));
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Faktur dibuat & diposting: ' . $invoice->number);
    }

    public function show(Invoice $invoice)
    {
        return view('sales.invoice.index', ['invoice' => $invoice->load(['items.item', 'customer', 'journalEntry']), 'items' => Invoice::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED', 'VOID']]);
    }

    public function print(Invoice $invoice)
    {
        AuditService::log('PRINT', 'SALES', $invoice->id, Invoice::class, null, ['invoice' => $invoice->number]);
        return view('sales.invoice.print', ['invoice' => $invoice->load(['items.item', 'customer', 'company'])]);
    }
}
