<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;

/**
 * Invoice register: read-only administration view over Sales Invoices
 * (the accounting source of truth). No second invoice table.
 */
class InvoiceRegisterController extends Controller
{
    use AppliesDataScope;

    public static function displayStatus(Invoice $invoice): string
    {
        if (in_array($invoice->status, ['CANCELLED', 'VOID'], true)) {
            return $invoice->status;
        }
        if ($invoice->status === 'PAID') {
            return 'LUNAS';
        }
        if (in_array($invoice->status, ['POSTED', 'PARTIALLY_PAID'], true)
            && $invoice->due_date && $invoice->due_date->isPast()) {
            return 'OVERDUE';
        }
        if ($invoice->status === 'POSTED') {
            return 'ISSUED';
        }

        return $invoice->status;
    }

    public function index(Request $request)
    {
        $items = Invoice::with(['customer', 'items.item'])
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->when($request->status, function ($q) use ($request) {
                if ($request->status === 'OVERDUE') {
                    $q->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->whereDate('due_date', '<', now());
                } elseif ($request->status === 'ISSUED') {
                    $q->where('status', 'POSTED');
                } else {
                    $q->where('status', $request->status);
                }
            })
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->from, fn ($q) => $q->whereDate('invoice_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('invoice_date', '<=', $request->to));
        $this->applyCompanyScope($items);
        $items = $items->orderByDesc('id')->paginate(20)->withQueryString();

        return view('registers.invoices', [
            'items' => $items, 'invoice' => null,
            'customers' => Customer::orderBy('name')->limit(200)->get(),
            'statuses' => ['DRAFT', 'ISSUED', 'PARTIALLY_PAID', 'PAID', 'OVERDUE', 'CANCELLED', 'VOID'],
        ]);
    }

    public function show(Invoice $invoice)
    {
        $this->ensureInScope($invoice);
        $invoice->load(['items.item', 'customer', 'salesOrder', 'journalEntry', 'company']);
        $prints = AuditLog::where('record_type', Invoice::class)->where('record_id', $invoice->id)
            ->whereIn('action', ['PRINT', 'PDF_DOWNLOAD'])->with('user')->orderByDesc('id')->limit(20)->get();

        return view('registers.invoices', [
            'invoice' => $invoice,
            'items' => Invoice::orderByDesc('id')->paginate(20),
            'customers' => Customer::orderBy('name')->limit(200)->get(),
            'statuses' => ['DRAFT', 'ISSUED', 'PARTIALLY_PAID', 'PAID', 'OVERDUE', 'CANCELLED', 'VOID'],
            'prints' => $prints,
        ]);
    }
}
