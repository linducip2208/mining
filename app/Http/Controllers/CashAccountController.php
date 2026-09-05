<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\AccountingMapping;
use App\Models\ChartOfAccount;
use App\Models\CashAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\AccountingService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashAccountController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = CashAccount::with('coa')
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%"))
            ->paginate(20)->withQueryString();
        return view('finance.cash.index', ['items' => $items, 'cashAccount' => null]);
    }

    public function create()
    {
        return view('finance.cash.form', ['cashAccount' => null, 'coas' => ChartOfAccount::whereIn('subtype', ['CASH', 'BANK'])->get(), 'companies' => \App\Models\Company::pluck('name', 'id')->all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|max:30',
            'name' => 'required|max:150',
            'type' => 'required|in:CASH,BANK',
            'bank_name' => 'nullable|max:100',
            'account_no' => 'nullable|max:50',
            'coa_id' => 'nullable|exists:chart_of_accounts,id',
            'opening_balance' => 'nullable|numeric',
            'status' => 'boolean',
        ]);
        $acc = CashAccount::create($validated);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        AuditService::created('FINANCE', $acc);
        return redirect()->route('cash-accounts.index')->with('success', 'Kas/bank ditambahkan.');
    }
}
