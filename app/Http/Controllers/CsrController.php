<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\Company;
use App\Models\CsrActivity;
use App\Models\CsrProgram;
use App\Models\Document;
use App\Models\Division;
use App\Services\AuditService;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CsrController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = CsrProgram::with(['activities', 'approvedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%")->orWhere('number', 'like', "%{$request->q}%"))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('csr.index', ['items' => $items, 'program' => null, 'statuses' => ['PROPOSAL', 'APPROVED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('csr.form', [
            'program' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => \App\Models\Site::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'name' => 'required|max:255',
            'description' => 'nullable',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $validated['number'] = NumberingService::generate('CSR');
        $validated['status'] = 'PROPOSAL';
        $validated['created_by'] = auth()->id();
        $program = CsrProgram::create($validated);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        AuditService::created('CSR', $program);
        return redirect()->route('csr.index')->with('success', 'Proposal CSR dibuat.');
    }

    public function show(CsrProgram $csr)
    {
        return view('csr.index', [
            'program' => $csr->load(['activities.expenses', 'documents', 'approvedBy']),
            'items' => CsrProgram::orderByDesc('id')->paginate(20),
            'statuses' => ['PROPOSAL', 'APPROVED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'],
        ]);
    }

    public function approve(CsrProgram $csr)
    {
        if (!auth()->user()->hasPermission('csr.approve')) {
            abort(403);
        }
        $csr->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'CSR', $csr->id, CsrProgram::class);
        return back()->with('success', 'Program CSR disetujui.');
    }

    public function addActivity(Request $request, CsrProgram $csr)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'date' => 'required|date',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);
        $csr->activities()->create($validated + ['status' => 'PLANNED']);
        return back()->with('success', 'Aktivitas ditambahkan.');
    }

    /**
     * CSR expense: journal Dr CSR Expense, Cr Cash (configurable).
     */
    public function addExpense(Request $request, CsrProgram $csr)
    {
        $validated = $request->validate([
            'csr_activity_id' => 'required|exists:csr_activities,id',
            'date' => 'required|date',
            'description' => 'required|max:255',
            'amount' => 'required|numeric|min:0.01',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
        ]);

        $activity = CsrActivity::find($validated['csr_activity_id']);
        $expense = $activity->expenses()->create($validated + ['created_by' => auth()->id()]);
        $activity->update(['actual_cost' => $activity->expenses()->sum('amount')]);

        try {
            $journal = \App\Services\AccountingService::post($csr->company_id, $validated['date'], [
                ['code' => \App\Services\AccountingService::map('CSR_EXPENSE'), 'debit' => $validated['amount'], 'memo' => 'CSR: ' . $validated['description']],
                ['code' => \App\Services\AccountingService::map('CASH_MAIN'), 'credit' => $validated['amount'], 'memo' => 'CSR: ' . $validated['description']],
            ], 'CSR', $expense->id, $csr->number, 'Beban CSR ' . $csr->number, 'CSR');
            $expense->update(['journal_entry_id' => $journal->id]);
        } catch (\InvalidArgumentException $e) {
            // journal mapping missing — expense recorded without journal
        }

        return back()->with('success', 'Beban CSR tercatat.');
    }
}
