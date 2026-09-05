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

class CoaController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = ChartOfAccount::withCount('journalLines')
            ->when($request->q, fn ($q) => $q->where('code', 'like', "%{$request->q}%")->orWhere('name', 'like', "%{$request->q}%"))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->orderBy('code')->paginate(25)->withQueryString();
        return view('finance.coa.index', ['items' => $items, 'coa' => null, 'types' => ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']]);
    }

    public function create()
    {
        return view('finance.coa.form', ['coa' => null, 'types' => ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|max:20|unique:chart_of_accounts,code',
            'name' => 'required|max:150',
            'type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'subtype' => 'nullable|max:50',
            'is_postable' => 'boolean',
            'status' => 'boolean',
        ]);
        $coa = ChartOfAccount::create($validated);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        AuditService::created('FINANCE', $coa);
        return redirect()->route('coa.index')->with('success', 'Akun ditambahkan.');
    }

    public function edit(ChartOfAccount $coa)
    {
        return view('finance.coa.form', ['coa' => $coa, 'types' => ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']]);
    }

    public function update(Request $request, ChartOfAccount $coa)
    {
        $validated = $request->validate([
            'code' => 'required|max:20|unique:chart_of_accounts,code,' . $coa->id,
            'name' => 'required|max:150',
            'type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'subtype' => 'nullable|max:50',
            'is_postable' => 'boolean',
            'status' => 'boolean',
        ]);
        $coa->update($validated);
        AuditService::updated('FINANCE', $coa);
        return redirect()->route('coa.index')->with('success', 'Akun diperbarui.');
    }
}
