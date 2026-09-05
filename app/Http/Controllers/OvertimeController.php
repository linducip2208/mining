<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Overtime;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OvertimeController extends Controller
{
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
        return view('hr.overtime.form', ['overtime' => null, 'employees' => Employee::where('status', 'ACTIVE')->get(), 'sites' => \App\Models\Site::pluck('name', 'id')->all()]);
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
        $validated['number'] = \App\Services\NumberingService::generate('OT');
        $validated['status'] = 'DRAFT';
        $validated['created_by'] = auth()->id();
        $ot = Overtime::create($validated);
        AuditService::created('HR', $ot);
        return redirect()->route('overtimes.index')->with('success', 'Lembur dicatat.');
    }

    public function approve(Overtime $overtime)
    {
        $overtime->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Lembur disetujui.');
    }
}
