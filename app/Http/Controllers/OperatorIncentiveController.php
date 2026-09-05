<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\OperatorIncentive;
use App\Models\PayrollRun;
use App\Models\PayrollDetail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\ApprovalService;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperatorIncentiveController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = OperatorIncentive::with(['employee', 'site', 'approvedBy'])
            ->when($request->period, fn ($q) => $q->where('period', $request->period))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('hr.incentive.index', ['items' => $items, 'incentive' => null, 'statuses' => ['DRAFT', 'APPROVED', 'INCLUDED_IN_PAYROLL', 'CANCELLED']]);
    }

    public function create()
    {
        return view('hr.incentive.form', [
            'incentive' => null,
            'employees' => Employee::where('status', 'ACTIVE')->get(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'site_id' => 'nullable|exists:sites,id',
            'basis' => 'required|in:TONNAGE,SHIFT,ACTIVITY,EQUIPMENT,LOCATION,TARGET',
            'period' => 'required|date_format:Y-m',
            'quantity' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'ref_type' => 'nullable|max:30',
            'notes' => 'nullable|max:500',
        ]);
        $validated['number'] = \App\Services\NumberingService::generate('INC');
        $validated['amount'] = round((float) $validated['quantity'] * (float) $validated['rate'], 2);
        $validated['status'] = 'DRAFT';
        $validated['created_by'] = auth()->id();

        $incentive = OperatorIncentive::create($validated);
        $this->ensureInScope(\App\Models\Employee::find($validated['employee_id']));
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        AuditService::created('PAYROLL', $incentive);
        return redirect()->route('operator-incentives.index')->with('success', 'Insentif dibuat. Wajib di-approve sebelum masuk payroll.');
    }

    public function show(OperatorIncentive $operator_incentive)
    {
        return view('hr.incentive.index', ['incentive' => $operator_incentive->load('employee'), 'items' => OperatorIncentive::orderByDesc('id')->paginate(20), 'statuses' => ['DRAFT', 'APPROVED', 'INCLUDED_IN_PAYROLL', 'CANCELLED']]);
    }

    /**
     * Insentif wajib approval sebelum payroll.
     */
    public function approve(OperatorIncentive $operator_incentive)
    {
        if (!auth()->user()->hasPermission('incentive.approve')) {
            abort(403);
        }
        if ($operator_incentive->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak valid.');
        }
        $operator_incentive->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'PAYROLL', $operator_incentive->id, OperatorIncentive::class);
        return back()->with('success', 'Insentif disetujui.');
    }
}
