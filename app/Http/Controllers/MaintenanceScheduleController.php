<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Crusher;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenancePart;
use App\Models\Item;
use App\Models\Site;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceScheduleController extends Controller
{
    public function index(Request $request)
    {
        $items = MaintenanceSchedule::with(['asset', 'equipment'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->orderBy('next_due')->paginate(20)->withQueryString();
        return view('maintenance.schedule.index', ['items' => $items, 'schedule' => null, 'types' => ['PREVENTIVE', 'CORRECTIVE'], 'intervalTypes' => ['RUNNING_HOUR', 'KM', 'DAY', 'MONTH']]);
    }

    public function create()
    {
        return view('maintenance.schedule.form', [
            'schedule' => null,
            'assets' => Asset::all(),
            'equipment' => \App\Models\Equipment::all(),
            'types' => ['PREVENTIVE', 'CORRECTIVE'],
            'intervalTypes' => ['RUNNING_HOUR', 'KM', 'DAY', 'MONTH'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'nullable|exists:assets,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'type' => 'required|in:PREVENTIVE,CORRECTIVE',
            'name' => 'required|max:150',
            'interval_type' => 'required|in:RUNNING_HOUR,KM,DAY,MONTH',
            'interval_value' => 'required|integer|min:1',
            'last_done' => 'nullable|date',
        ]);
        MaintenanceSchedule::create($validated);
        return redirect()->route('maintenance-schedules.index')->with('success', 'Jadwal dibuat.');
    }
}
