<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\NumberingService;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = Overtime::with(['employee', 'approvedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from, fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->orderByDesc('date')->paginate(20)->withQueryString();

        return view('hr.overtime.index', ['items' => $items, 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('hr.overtime.form', ['overtime' => null, 'employees' => Employee::where('status', 'ACTIVE')->get(), 'sites' => Site::pluck('name', 'id')->all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'reason' => 'required|max:255',
            'site_id' => 'nullable|exists:sites,id',
        ]);
        $validated['number'] = NumberingService::generate('OT');
        $validated['status'] = 'DRAFT';
        $validated['created_by'] = auth()->id();
        $ot = Overtime::create($validated);
        $this->ensureInScope(Employee::find($validated['employee_id']));
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        AuditService::created('HR', $ot);

        return redirect()->route('overtimes.index')->with('success', 'Lembur dicatat.');
    }

    public function approve(Overtime $overtime)
    {
        if ($overtime->status === 'APPROVED') {
            return back()->with('error', 'Lembur sudah disetujui.');
        }
        if (! in_array($overtime->status, ['DRAFT', 'SUBMITTED'], true)) {
            return back()->with('error', 'Hanya lembur DRAFT/SUBMITTED yang dapat disetujui.');
        }
        $overtime->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'HR', $overtime->id, Overtime::class);

        return back()->with('success', 'Lembur disetujui.');
    }

    public function reject(Overtime $overtime)
    {
        if (! in_array($overtime->status, ['DRAFT', 'SUBMITTED'], true)) {
            return back()->with('error', 'Hanya lembur DRAFT/SUBMITTED yang dapat ditolak.');
        }
        $overtime->update(['status' => 'REJECTED']);
        AuditService::log('REJECT', 'HR', $overtime->id, Overtime::class);

        return back()->with('success', 'Lembur ditolak.');
    }
}
