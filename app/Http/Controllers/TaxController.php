<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Invoice;
use App\Models\VendorBill;
use App\Models\TaxCode;
use App\Models\TaxTransaction;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $items = TaxTransaction::with(['taxCode', 'company'])
            ->when($request->period, fn ($q) => $q->where('period', $request->period))
            ->when($request->kind, fn ($q) => $q->where('kind', $request->kind))
            ->orderByDesc('trx_date')->paginate(25)->withQueryString();

        $taxCodes = TaxCode::all();
        $ppnOut = TaxTransaction::where('kind', 'PPN')->where('transaction_type', 'SALES')->where('status', 'ACTIVE')->sum('tax_amount');
        $ppnIn = TaxTransaction::where('kind', 'PPN')->where('transaction_type', 'PURCHASE')->where('status', 'ACTIVE')->sum('tax_amount');

        return view('finance.tax.index', [
            'items' => $items,
            'taxCodes' => $taxCodes,
            'ppnOut' => $ppnOut,
            'ppnIn' => $ppnIn,
            'kinds' => ['PPN', 'PBB', 'SPT', 'OTHER'],
        ]);
    }

    /**
     * Manual tax entry (e.g. PBB assessment, SPT).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tax_code_id' => 'required|exists:tax_codes,id',
            'kind' => 'required|in:PPN,PBB,SPT,OTHER',
            'transaction_type' => 'required|in:SALES,PURCHASE,OTHER',
            'trx_date' => 'required|date',
            'tax_invoice_no' => 'nullable|max:100',
            'tax_base' => 'required|numeric|min:0',
        ]);

        $code = TaxCode::find($validated['tax_code_id']);
        TaxTransaction::create([
            'company_id' => \App\Models\Company::value('id') ?? 1,
            'tax_code_id' => $validated['tax_code_id'],
            'transaction_type' => $validated['transaction_type'],
            'trx_date' => $validated['trx_date'],
            'period' => substr($validated['trx_date'], 0, 7),
            'tax_invoice_no' => $validated['tax_invoice_no'] ?? null,
            'tax_base' => $validated['tax_base'],
            'tax_amount' => round($validated['tax_base'] * $code->rate / 100, 2),
            'kind' => $validated['kind'],
        ]);

        return back()->with('success', 'Transaksi pajak tercatat.');
    }
}
