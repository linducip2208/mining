<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Invoice;
use App\Models\LetterRegister;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Http\Request;

class AdministrationController extends Controller
{
    use AppliesDataScope;

    public function dashboard(Request $request)
    {
        $month = now()->format('Y-m');
        $letters = LetterRegister::query();
        $this->applyCompanyScope($letters);
        $invoices = Invoice::query();
        $this->applyCompanyScope($invoices);

        $lettersMonth = (clone $letters)->where('letter_date', 'like', $month.'%')->count();
        $lettersPending = (clone $letters)->whereIn('status', ['DRAFT', 'NUMBER_RESERVED', 'REVIEW'])->count();
        $lettersUnsent = (clone $letters)->whereIn('status', ['APPROVED', 'SIGNED', 'PUBLISHED'])->count();
        $invoicesMonth = (clone $invoices)->where('invoice_date', 'like', $month.'%')->count();
        $invoicesUnpaid = (clone $invoices)->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->sum('total') - (clone $invoices)->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->sum('paid_amount');
        $invoicesOverdue = (clone $invoices)->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->whereDate('due_date', '<', now())->count();
        $paymentsMonth = Payment::where('payment_date', 'like', $month.'%')->sum('amount');
        $receiptsMonth = Receipt::where('receipt_date', 'like', $month.'%')->count();

        $latestLetters = (clone $letters)->orderByDesc('id')->limit(8)->with('type')->get();
        $overdueInvoices = (clone $invoices)->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->whereDate('due_date', '<', now())->orderBy('due_date')->limit(8)->with('customer')->get();
        $pendingApprovals = (clone $letters)->where('status', 'REVIEW')->orderByDesc('id')->limit(8)->with('type')->get();
        $latestReceipts = Receipt::orderByDesc('id')->limit(8)->get();

        return view('administration.dashboard', compact(
            'lettersMonth', 'lettersPending', 'lettersUnsent', 'invoicesMonth',
            'invoicesUnpaid', 'invoicesOverdue', 'paymentsMonth', 'receiptsMonth',
            'latestLetters', 'overdueInvoices', 'pendingApprovals', 'latestReceipts'
        ));
    }
}
