<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\CashAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\NumberingService;
use App\Services\NumberToWordsService;
use App\Services\PrintDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Receipt register (kwitansi). Always linked to a real payment —
 * never creates money movement itself.
 */
class ReceiptController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Receipt::with(['customer', 'payment', 'invoice'])
            ->when($request->q, fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', "%{$request->q}%")->orWhere('payer_name', 'like', "%{$request->q}%")))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from, fn ($q) => $q->whereDate('receipt_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('receipt_date', '<=', $request->to));
        $this->applyCompanyScope($items);
        $items = $items->orderByDesc('id')->paginate(20)->withQueryString();

        return view('registers.receipts', [
            'items' => $items, 'receipt' => null,
            'statuses' => Receipt::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        $payment = $request->payment_id ? Payment::with(['customer', 'allocations'])->find($request->payment_id) : null;

        return view('registers.receipt-form', [
            'receipt' => null,
            'payment' => $payment,
            'payments' => Payment::with('customer')->orderByDesc('id')->limit(100)->get(),
            'invoices' => $payment ? Invoice::where('customer_id', $payment->customer_id)->orderByDesc('id')->limit(50)->get() : collect(),
            'cashAccounts' => CashAccount::where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'receipt_date' => 'required|date',
            'invoice_id' => 'nullable|exists:invoices,id',
            'description' => 'nullable|max:500',
            'payment_method' => 'required|in:CASH,TRANSFER,GIRO,DEPOSIT',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'reference_no' => 'nullable|max:100',
        ]);
        $payment = Payment::with('customer')->findOrFail($validated['payment_id']);
        $this->ensureCompanyInScope($payment->company_id);
        if ($payment->status !== 'POSTED') {
            return back()->with('error', 'Kwitansi hanya dapat dibuat dari pembayaran yang sudah POSTED.');
        }
        // one active kwitansi per payment — duplicates must be voided first
        $existingActive = Receipt::where('payment_id', $payment->id)->whereNotIn('status', ['VOID'])->first();
        if ($existingActive) {
            return back()->with('error', 'Pembayaran ini sudah memiliki kwitansi aktif: '.$existingActive->number.'. Void kwitansi lama dulu bila perlu menerbitkan ulang.');
        }

        $receipt = DB::transaction(function () use ($validated, $payment) {
            NumberingService::ensure('KWITANSI', (string) Setting::get('numbering.receipt_format', 'KW/{SEQ:4}/{MONTH_ROMAN}/{YEAR}'));
            $number = NumberingService::generate('KWITANSI', $payment->company_id);

            return Receipt::create([
                'number' => $number,
                'receipt_date' => $validated['receipt_date'],
                'company_id' => $payment->company_id,
                'payment_id' => $payment->id,
                'customer_id' => $payment->customer_id,
                'payer_name' => $payment->customer?->name ?? 'Tunai',
                'invoice_id' => $validated['invoice_id'] ?? null,
                'description' => $validated['description'] ?? ('Pembayaran '.$payment->number),
                'amount' => $payment->amount,
                'payment_method' => $validated['payment_method'],
                'cash_account_id' => $validated['cash_account_id'] ?? $payment->cash_account_id,
                'reference_no' => $validated['reference_no'] ?? null,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
        });
        AuditService::created('RECEIPT', $receipt);

        return redirect()->route('receipts.show', $receipt)->with('success', 'Kwitansi dibuat: '.$receipt->number);
    }

    public function show(Receipt $receipt)
    {
        $this->ensureInScope($receipt);
        $receipt->load(['customer', 'payment.allocations', 'invoice', 'cashAccount', 'company', 'creator', 'approver']);

        return view('registers.receipts', [
            'receipt' => $receipt,
            'items' => Receipt::orderByDesc('id')->paginate(20),
            'statuses' => Receipt::STATUSES,
            'terbilang' => NumberToWordsService::rupiah((float) $receipt->amount),
        ]);
    }

    public function issue(Receipt $receipt)
    {
        $this->ensureInScope($receipt);
        if ($receipt->status !== 'DRAFT') {
            return back()->with('error', 'Hanya DRAFT yang dapat diterbitkan.');
        }
        $receipt->update(['status' => 'ISSUED', 'approved_by' => auth()->id()]);
        AuditService::log('UPDATE', 'RECEIPT', $receipt->id, Receipt::class, null, ['event' => 'issued']);

        return back()->with('success', 'Kwitansi diterbitkan.');
    }

    public function confirm(Receipt $receipt)
    {
        $this->ensureInScope($receipt);
        if ($receipt->status !== 'ISSUED') {
            return back()->with('error', 'Hanya ISSUED yang dapat dikonfirmasi.');
        }
        $receipt->update(['status' => 'CONFIRMED']);
        AuditService::log('UPDATE', 'RECEIPT', $receipt->id, Receipt::class, null, ['event' => 'confirmed']);

        return back()->with('success', 'Kwitansi dikonfirmasi lunas.');
    }

    public function void(Receipt $receipt)
    {
        $this->ensureInScope($receipt);
        if ($receipt->status === 'VOID') {
            return back()->with('error', 'Kwitansi sudah void.');
        }
        $receipt->update(['status' => 'VOID']);
        AuditService::log('VOID', 'RECEIPT', $receipt->id, Receipt::class);

        return back()->with('success', 'Kwitansi divoid. Tidak menghapus pembayaran asal.');
    }

    public function print(Receipt $receipt)
    {
        $this->ensureInScope($receipt);
        $reprint = $receipt->printed_at !== null;
        $receipt->update(['printed_at' => now()]);
        AuditService::log('PRINT', 'RECEIPT', $receipt->id, Receipt::class, null, ['number' => $receipt->number]);

        return view('print.receipt', PrintDocumentService::context([
            'receipt' => $receipt->load(['customer', 'payment.allocations.invoice', 'payment.customer', 'invoice', 'cashAccount', 'company']),
            'terbilang' => NumberToWordsService::rupiah((float) $receipt->amount),
            'reprint' => $reprint,
        ]));
    }
}
