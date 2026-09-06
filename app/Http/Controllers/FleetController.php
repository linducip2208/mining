<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentAssignment;
use App\Models\Shift;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\FleetService;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    use AppliesDataScope;

    public function dashboard(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $data = FleetService::fleetSummary(
            $request->company_id ?: null,
            $request->site_id ?: null,
            $from, $to
        );
        return view('fleet.dashboard', $data + [
            'from' => $from, 'to' => $to,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function availability(Request $request)
    {
        return $this->kpiView($request, 'fleet.availability', 'Ketersediaan Armada');
    }

    public function utilization(Request $request)
    {
        return $this->kpiView($request, 'fleet.utilization', 'Utilisasi Armada');
    }

    public function downtime(Request $request)
    {
        return $this->kpiView($request, 'fleet.downtime', 'Downtime Armada');
    }

    public function cost(Request $request)
    {
        return $this->kpiView($request, 'fleet.cost', 'Biaya Armada');
    }

    protected function kpiView(Request $request, string $view, string $title)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $data = FleetService::fleetSummary(
            $request->company_id ?: null,
            $request->site_id ?: null,
            $from, $to
        );
        return view($view, $data + [
            'title' => $title,
            'from' => $from, 'to' => $to,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function meters(Request $request)
    {
        $logs = \App\Models\EquipmentMeterLog::with(['equipment', 'shift', 'operator'])
            ->when($request->equipment_id, fn ($q) => $q->where('equipment_id', $request->equipment_id))
            ->when($request->from, fn ($q) => $q->whereDate('log_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('log_date', '<=', $request->to))
            ->orderByDesc('log_date')->paginate(20)->withQueryString();
        return view('fleet.meters', [
            'logs' => $logs,
            'units' => Equipment::orderBy('code')->get(),
            'shifts' => Shift::pluck('name', 'id')->all(),
            'operators' => Employee::where('status', 'ACTIVE')->get(),
        ]);
    }

    public function storeMeter(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'log_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'hm_start' => 'nullable|numeric|min:0',
            'hm_end' => 'nullable|numeric|min:0',
            'km_start' => 'nullable|numeric|min:0',
            'km_end' => 'nullable|numeric|min:0',
            'operating_hours' => 'nullable|numeric|min:0',
            'idle_hours' => 'nullable|numeric|min:0',
            'status' => 'required|in:AVAILABLE,IN_USE,IDLE,MAINTENANCE,BREAKDOWN,STANDBY',
            'operator_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|max:1000',
        ]);
        $eq = Equipment::find($validated['equipment_id']);
        $this->ensureInScope($eq);
        try {
            FleetService::recordMeterLog($validated);
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->with('success', 'HM/odometer tercatat.');
    }

    public function inspections(Request $request)
    {
        $items = \App\Models\EquipmentInspection::with(['equipment', 'shift', 'inspector'])
            ->when($request->equipment_id, fn ($q) => $q->where('equipment_id', $request->equipment_id))
            ->orderByDesc('inspection_date')->paginate(20)->withQueryString();
        return view('fleet.inspections', [
            'items' => $items,
            'units' => Equipment::orderBy('code')->get(),
            'shifts' => Shift::pluck('name', 'id')->all(),
        ]);
    }

    public function storeInspection(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'inspection_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'result' => 'required|in:PASS,FAIL,CONDITIONAL',
            'findings' => 'nullable|max:2000',
        ]);
        $this->ensureInScope(Equipment::find($validated['equipment_id']));
        $validated['checklist'] = $request->input('checklist', []);
        FleetService::recordInspection($validated);
        return back()->with('success', 'Inspeksi tersimpan.' . ($validated['result'] === 'FAIL' ? ' Unit otomatis BREAKDOWN.' : ''));
    }

    public function assignments(Request $request)
    {
        $items = EquipmentAssignment::with(['equipment', 'employee', 'site', 'shift'])
            ->when($request->date, fn ($q) => $q->whereDate('date', $request->date))
            ->orderByDesc('date')->paginate(20)->withQueryString();
        return view('fleet.assignments', [
            'items' => $items,
            'units' => Equipment::whereNotIn('status', ['RETIRED', 'DISPOSED'])->orderBy('code')->get(),
            'operators' => Employee::where('status', 'ACTIVE')->get(),
            'sites' => Site::pluck('name', 'id')->all(),
            'shifts' => Shift::pluck('name', 'id')->all(),
        ]);
    }

    public function storeAssignment(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'employee_id' => 'nullable|exists:employees,id',
            'site_id' => 'nullable|exists:sites,id',
            'pit_id' => 'nullable|exists:pits,id',
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'working_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|max:1000',
        ]);
        $this->ensureInScope(Equipment::find($validated['equipment_id']));
        EquipmentAssignment::create($validated + ['status' => 'ASSIGNED', 'created_by' => auth()->id()]);
        AuditService::created('FLEET', EquipmentAssignment::latest()->first());
        return back()->with('success', 'Assignment tersimpan.');
    }
}
