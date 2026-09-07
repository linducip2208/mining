<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\AuditService;
use App\Services\PrintDocumentService;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Invoice::with(['customer', 'items', 'salesOrder'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->overdue, fn ($q) => $q->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->whereDate('due_date', '<', now()))

            ->when(! is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('sales.invoice.index', ['items' => $items, 'invoice' => null, 'statuses' => ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED', 'VOID']]);
    }

    public function create()
    {
        return view('sales.invoice.create', [
            'salesOrders' => SalesOrder::whereIn('status', ['PARTIALLY_DELIVERED', 'COMPLETED'])
                ->whereDoesntHave('invoice', fn ($q) => $q->whereNotIn('status', ['CANCELLED', 'VOID']))
                ->with(['customer', 'items'])->get(),
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
            $invoice = SalesService::createInvoice($so, Carbon::parse($validated['invoice_date']), null, $request->boolean('use_deposit'));
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Faktur dibuat & diposting: '.$invoice->number);
    }

    public function show(Invoice $invoice)
    {
        return view('sales.invoice.index', ['invoice' => $invoice->load(['items.item', 'customer', 'journalEntry']), 'items' => Invoice::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED', 'VOID']]);
    }

    public function print(Invoice $invoice)
    {
        AuditService::log('PRINT', 'SALES', $invoice->id, Invoice::class, null, ['invoice' => $invoice->number]);

        return view('print.invoice', PrintDocumentService::context(['invoice' => $invoice->load(['items.item', 'customer', 'company']), 'documentTitle' => 'Invoice', 'watermark' => $invoice->status === 'VOID' ? 'VOID' : ($invoice->status === 'CANCELLED' ? 'DIBATALKAN' : null)]));
    }

    public function pdf(Invoice $invoice)
    {
        AuditService::log('PDF_DOWNLOAD', 'SALES', $invoice->id, Invoice::class, null, ['invoice' => $invoice->number]);

        return PrintDocumentService::pdf('print.invoice', ['invoice' => $invoice->load(['items.item', 'customer', 'company']), 'documentTitle' => 'Invoice', 'watermark' => $invoice->status === 'VOID' ? 'VOID' : ($invoice->status === 'CANCELLED' ? 'DIBATALKAN' : null)], 'Invoice-'.$invoice->number);
    }
}
