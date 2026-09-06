<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\FuelIssue;
use App\Models\FuelTank;
use App\Models\Shift;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\FuelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelIssueController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = FuelIssue::with(['tank', 'equipment', 'operator'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->orderByDesc('issue_date')->paginate(20)->withQueryString();
        return view('fuel.issues.index', ['items' => $items, 'statuses' => ['DRAFT', 'APPROVED', 'POSTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('fuel.issues.form', [
            'issue' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'tanks' => FuelTank::where('status', true)->get(),
            'units' => Equipment::whereNotIn('status', ['RETIRED', 'DISPOSED'])->orderBy('code')->get(),
            'operators' => Employee::where('status', 'ACTIVE')->get(),
            'shifts' => Shift::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('fuel.create')) {
            abort(403);
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'issue_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'fuel_tank_id' => 'required|exists:fuel_tanks,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'vehicle_plate' => 'nullable|max:30',
            'operator_id' => 'nullable|exists:employees,id',
            'hm_before' => 'nullable|numeric|min:0',
            'hm_after' => 'nullable|numeric|min:0',
            'liter' => 'required|numeric|min:0.001',
            'reference' => 'nullable|max:100',
            'notes' => 'nullable|max:1000',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        $issue = FuelIssue::create($validated + [
            'number' => \App\Services\NumberingService::generate('FUEL', $validated['company_id']),
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        AuditService::created('FUEL', $issue);
        return redirect()->route('fuel-issues.show', $issue)->with('success', 'Fuel issue dibuat.');
    }

    public function show(FuelIssue $fuel_issue)
    {
        return view('fuel.issues.index', [
            'issue' => $fuel_issue->load(['tank', 'equipment.category', 'operator', 'journalEntry']),
            'items' => FuelIssue::orderByDesc('id')->paginate(20),
            'statuses' => ['DRAFT', 'APPROVED', 'POSTED', 'CANCELLED'],
        ]);
    }

    public function approve(FuelIssue $fuel_issue)
    {
        if (!auth()->user()->hasPermission('fuel.approve')) {
            abort(403);
        }
        if ($fuel_issue->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak valid.');
        }
        $fuel_issue->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'FUEL', $fuel_issue->id, FuelIssue::class);
        return back()->with('success', 'Fuel issue disetujui.');
    }

    public function post(FuelIssue $fuel_issue)
    {
        if (!auth()->user()->hasPermission('fuel.post')) {
            abort(403);
        }
        try {
            FuelService::issue($fuel_issue->fresh());
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'BBM diterbitkan: stok tangki −, L/H & variansi dihitung, jurnal tersimpan.');
    }
}
