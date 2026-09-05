<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Overtime;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $items = Leave::with(['employee', 'approvedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();
        return view('hr.leave.index', ['items' => $items, 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('hr.leave.form', ['leave' => null, 'employees' => Employee::where('status', 'ACTIVE')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:ANNUAL,SICK,UNPAID,MATERNITY,OTHER',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|max:1000',
        ]);
        $validated['days'] = \Carbon\Carbon::parse($validated['start_date'])->diffInDays($validated['end_date']) + 1;
        $validated['number'] = \App\Services\NumberingService::generate('LV');
        $validated['status'] = 'DRAFT';
        $validated['created_by'] = auth()->id();
        $leave = Leave::create($validated);
        AuditService::created('HR', $leave);
        return redirect()->route('leaves.index')->with('success', 'Pengajuan cuti dibuat.');
    }

    public function approve(Leave $leave)
    {
        $leave->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Cuti disetujui.');
    }
}
