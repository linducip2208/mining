<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\CashAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\SalesService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Payment::with(['customer', 'supplier', 'cashAccount'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('sales.payment.index', ['items' => $items, 'payment' => null, 'statuses' => ['DRAFT', 'POSTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('sales.payment.form', [
            'payment' => null,
            'customers' => Customer::where('status', true)->get(),
            'cashAccounts' => CashAccount::where('status', true)->get(),
            'companies' => Company::pluck('name', 'id')->all(),
        ]);
    }

    /**
     * Customer payment receive (FIFO invoice allocation + journal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'payment_date' => 'required|date',
            'method' => 'required|in:CASH,BANK_TRANSFER,CHECK,GIRO,VIRTUAL_ACCOUNT',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_no' => 'nullable|max:100',
            'invoice_ids' => 'array',
            'invoice_ids.*' => 'exists:invoices,id',
        ]);

        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureInScope(\App\Models\Customer::find($validated['customer_id']));

        try {
            $payment = SalesService::receivePayment(
                (int) $validated['company_id'],
                (int) $validated['customer_id'],
                (float) $validated['amount'],
                $validated['payment_date'],
                $validated['method'],
                $validated['cash_account_id'] ?? null,
                $validated['reference_no'] ?? null,
                $validated['invoice_ids'] ?? []
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditService::created('FINANCE', $payment);
        return redirect()->route('payments.index')->with('success', 'Pembayaran tercatat: ' . $payment->number);
    }

    public function show(Payment $payment)
    {
        return view('sales.payment.index', ['payment' => $payment->load(['allocations', 'customer', 'cashAccount']), 'items' => Payment::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'POSTED', 'CANCELLED']]);
    }
}
