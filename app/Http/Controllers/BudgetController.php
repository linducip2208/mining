<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Division;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\BudgetService;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Budget::with(['company', 'site'])
            ->when($request->year, fn ($q) => $q->where('year', $request->year))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->orderByDesc('year')->paginate(20)->withQueryString();
        return view('budget.index', [
            'items' => $items,
            'statuses' => ['DRAFT', 'APPROVED', 'REVISED', 'CLOSED', 'CANCELLED'],
            'defaultYear' => now()->year,
        ]);
    }

    public function create()
    {
        return view('budget.form', [
            'budget' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'divisions' => Division::pluck('name', 'id')->all(),
            'departments' => Department::pluck('name', 'id')->all(),
            'costCenters' => CostCenter::pluck('name', 'id')->all(),
            'coas' => ChartOfAccount::whereIn('type', ['EXPENSE', 'ASSET'])->where('is_postable', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('budget.create')) {
            abort(403);
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'division_id' => 'nullable|exists:divisions,id',
            'department_id' => 'nullable|exists:departments,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'year' => 'required|integer|min:2000|max:2100',
            'type' => 'required|in:OPEX,CAPEX',
            'notes' => 'nullable|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            'lines.*.period' => 'nullable|date_format:Y-m',
            'lines.*.amount' => 'required|numeric|min:0',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        $budget = DB::transaction(function () use ($validated) {
            $budget = Budget::create([
                'number' => NumberingService::generate('BGT', $validated['company_id']),
                'company_id' => $validated['company_id'],
                'site_id' => $validated['site_id'] ?? null,
                'division_id' => $validated['division_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'year' => $validated['year'],
                'type' => $validated['type'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'DRAFT',
                'version' => 1,
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['lines'] as $line) {
                $budget->lines()->create($line);
            }
            return $budget;
        });
        AuditService::created('BUDGET', $budget);
        return redirect()->route('budgets.show', $budget)->with('success', 'Budget dibuat.');
    }

    public function show(Budget $budget)
    {
        return view('budget.show', [
            'budget' => $budget->load(['lines.chartOfAccount', 'company', 'site']),
            'report' => BudgetService::budgetReport($budget),
        ]);
    }

    public function approve(Budget $budget)
    {
        if (!auth()->user()->hasPermission('budget.approve')) {
            abort(403);
        }
        if (!in_array($budget->status, ['DRAFT', 'REVISED'])) {
            return back()->with('error', 'Status tidak valid.');
        }
        $budget->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'BUDGET', $budget->id, Budget::class);
        return back()->with('success', 'Budget disetujui.');
    }

    public function revise(Budget $budget)
    {
        if (!auth()->user()->hasPermission('budget.revise')) {
            abort(403);
        }
        if (!in_array($budget->status, ['APPROVED', 'REVISED'])) {
            return back()->with('error', 'Hanya budget APPROVED yang bisa direvisi.');
        }
        $budget->update(['status' => 'REVISED', 'version' => $budget->version + 1, 'approved_by' => null]);
        AuditService::log('UPDATE', 'BUDGET', $budget->id, Budget::class, null, ['revised_to' => $budget->version]);
        return back()->with('success', 'Budget dibuka untuk revisi (v' . $budget->version . ').');
    }

    public function close(Budget $budget)
    {
        if (!auth()->user()->hasPermission('budget.approve')) {
            abort(403);
        }
        $budget->update(['status' => 'CLOSED']);
        AuditService::log('CLOSE', 'BUDGET', $budget->id, Budget::class);
        return back()->with('success', 'Budget ditutup.');
    }
}
