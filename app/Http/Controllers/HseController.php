<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\HseActivity;
use App\Models\HseCorrectiveAction;
use App\Models\HsePermit;
use App\Models\HseReport;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\HseService;
use Illuminate\Http\Request;

class HseController extends Controller
{
    use AppliesDataScope;

    public function dashboard(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $data = HseService::dashboard(
            $request->company_id ? (int) $request->company_id : null,
            $request->site_id ? (int) $request->site_id : null,
            $from, $to
        );
        return view('hse.dashboard', $data + [
            'from' => $from, 'to' => $to,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function reports(Request $request)
    {
        $items = HseReport::with(['site', 'employee', 'equipment'])
            ->when($request->kind, fn ($q) => $q->where('kind', $request->kind))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->orderByDesc('occurred_at')->paginate(20)->withQueryString();
        return view('hse.reports.index', [
            'items' => $items,
            'kinds' => ['INCIDENT' => 'Insiden', 'NEAR_MISS' => 'Near Miss', 'HAZARD' => 'Laporan Bahaya'],
            'statuses' => ['OPEN', 'IN_PROGRESS', 'CLOSED', 'CANCELLED'],
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function createReport()
    {
        return view('hse.reports.form', [
            'report' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'employees' => Employee::where('status', 'ACTIVE')->get(),
            'units' => Equipment::orderBy('code')->get(),
            'severities' => HseService::severities(),
        ]);
    }

    public function storeReport(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'kind' => 'required|in:INCIDENT,NEAR_MISS,HAZARD',
            'location' => 'nullable|max:255',
            'occurred_at' => 'required|date',
            'employee_id' => 'nullable|exists:employees,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'severity' => 'required|max:30',
            'description' => 'required|max:5000',
            'cause' => 'nullable|max:5000',
            'immediate_action' => 'nullable|max:5000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('hse', 'private');
        }
        $report = HseService::report($validated);
        return redirect()->route('hse.reports.show', $report)->with('success', 'Laporan tersimpan: ' . $report->number);
    }

    public function showReport(HseReport $hse_report)
    {
        return view('hse.reports.show', [
            'report' => $hse_report->load(['correctiveActions.responsible', 'employee', 'equipment', 'site']),
            'employees' => Employee::where('status', 'ACTIVE')->get(),
        ]);
    }

    public function addAction(Request $request, HseReport $hse_report)
    {
        $validated = $request->validate([
            'action' => 'required|max:2000',
            'responsible_id' => 'required|exists:employees,id',
            'due_date' => 'required|date',
        ]);
        try {
            HseService::addAction($hse_report->id, $validated);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Corrective action ditambahkan.');
    }

    public function investigate(Request $request, HseReport $hse_report)
    {
        if (!auth()->user()->hasPermission('hse.update')) {
            abort(403);
        }
        $validated = $request->validate([
            'root_cause' => 'required|max:5000',
            'cause' => 'nullable|max:5000',
        ]);
        try {
            HseService::investigate($hse_report, $validated['root_cause'], $validated['cause'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Investigasi tersimpan — root cause tercatat.');
    }

    public function verifyAction(HseCorrectiveAction $action)
    {
        if (!auth()->user()->hasPermission('hse.close')) {
            abort(403);
        }
        try {
            HseService::verifyAction($action);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Action terverifikasi.');
    }

    public function closeAction(Request $request, \App\Models\HseCorrectiveAction $action)
    {
        if (!auth()->user()->hasPermission('hse.close') && !auth()->user()->hasPermission('hse.update')) {
            abort(403);
        }
        $validated = $request->validate([
            'evidence' => 'required|max:5000',
        ]);
        $file = $request->hasFile('evidence_file') ? $request->file('evidence_file')->store('hse', 'private') : null;
        try {
            HseService::closeAction($action, $validated['evidence'], $file);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Action ditutup dengan evidence.');
    }

    public function closeReport(HseReport $hse_report)
    {
        if (in_array($hse_report->status, ['CLOSED', 'CANCELLED'])) {
            return back()->with('error', 'Status tidak valid.');
        }
        \App\Services\ApprovalService::submit('HSE', 'HSE_CLOSE', $hse_report);
        return back()->with('success', $hse_report->fresh()->status === 'SUBMITTED'
            ? 'Penutupan kasus diajukan ke approval center.'
            : 'Kasus ditutup.');
    }

    public function permits(Request $request)
    {
        $items = HsePermit::with(['site', 'requester'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status));
        $this->applyCompanyScope($items);
        $this->applySiteScope($items);
        $items = $items->orderByDesc('valid_from')->paginate(20)->withQueryString();
        return view('hse.permits.index', [
            'items' => $items,
            'statuses' => ['DRAFT', 'APPROVED', 'ACTIVE', 'EXPIRED', 'CLOSED', 'REJECTED'],
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'employees' => Employee::where('status', 'ACTIVE')->get(),
            'units' => Equipment::orderBy('code')->get(),
        ]);
    }

    public function storePermit(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'work_type' => 'required|max:100',
            'location' => 'nullable|max:255',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'requester_id' => 'nullable|exists:employees,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'precautions' => 'nullable|max:2000',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $permit = HsePermit::create($validated + [
            'number' => \App\Services\NumberingService::generate('PTW'),
            'status' => 'DRAFT',
            'created_by' => auth()->id(),
        ]);
        AuditService::created('HSE', $permit);
        return back()->with('success', 'Permit dibuat: ' . $permit->number);
    }

    public function approvePermit(HsePermit $permit)
    {
        if ($permit->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak valid.');
        }
        \App\Services\ApprovalService::submit('HSE', 'HSE_PERMIT', $permit);
        return back()->with('success', $permit->fresh()->status === 'SUBMITTED'
            ? 'Permit diajukan ke approval center.'
            : 'Permit disetujui.');
    }

    public function activities(Request $request)
    {
        $items = HseActivity::with(['site'])
            ->when($request->kind, fn ($q) => $q->where('kind', $request->kind));
        $this->applyCompanyScope($items);
        $this->applySiteScope($items);
        $items = $items->orderByDesc('activity_date')->paginate(20)->withQueryString();
        return view('hse.activities.index', [
            'items' => $items,
            'kinds' => ['INSPECTION' => 'Inspeksi', 'TOOLBOX' => 'Toolbox Meeting', 'TRAINING' => 'Training', 'PPE_CHECK' => 'Cek APD'],
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function storeActivity(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'kind' => 'required|in:INSPECTION,TOOLBOX,TRAINING,PPE_CHECK',
            'topic' => 'required|max:255',
            'activity_date' => 'required|date',
            'participants' => 'nullable|max:2000',
            'result' => 'nullable|max:2000',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $activity = HseActivity::create($validated + ['created_by' => auth()->id()]);
        AuditService::created('HSE', $activity);
        return back()->with('success', 'Kegiatan tercatat.');
    }
}
