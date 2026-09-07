<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\MaintenancePart;
use App\Models\Site;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\MaintenanceService;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $items = WorkOrder::with(['equipment', 'asset'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))

            ->when(! is_null($companies = auth()->user()?->accessibleCompanyIds()), fn ($w) => $w->whereIn('company_id', $companies))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('maintenance.wo.index', ['items' => $items, 'workOrder' => null, 'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'IN_PROGRESS', 'COMPLETED', 'CLOSED', 'CANCELLED']]);
    }

    public function create()
    {
        return view('maintenance.wo.form', [
            'workOrder' => null,
            'equipment' => Equipment::all(),
            'assets' => Asset::all(),
            'sites' => Site::pluck('name', 'id')->all(),
            'items' => Item::where('type', 'SPAREPART')->get(),
            'warehouses' => Warehouse::where('type', 'WAREHOUSE')->get(),
            'employees' => Employee::where('status', 'ACTIVE')->get(),
            'companies' => Company::pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);

        $wo = DB::transaction(function () use ($validated, $request) {
            $wo = WorkOrder::create($validated + [
                'number' => NumberingService::generate('WO', $validated['company_id']),
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
            foreach ($request->input('tasks', []) as $i => $task) {
                if (! empty($task['description'])) {
                    $wo->tasks()->create(['description' => $task['description'], 'sort' => $i]);
                }
            }
            foreach ($request->input('parts', []) as $part) {
                if (! empty($part['item_id']) && $part['qty'] > 0) {
                    $wo->parts()->create([
                        'item_id' => $part['item_id'],
                        'warehouse_id' => $part['warehouse_id'] ?? null,
                        'qty' => $part['qty'],
                        'unit_cost' => (float) Item::find($part['item_id'])->avg_cost,
                    ]);
                }
            }
            foreach ($request->input('technicians', []) as $tech) {
                if (! empty($tech['employee_id'])) {
                    $wo->technicians()->create(['employee_id' => $tech['employee_id'], 'hours' => $tech['hours'] ?? 0]);
                }
            }

            return $wo;
        });

        AuditService::created('MAINTENANCE', $wo);

        return redirect()->route('work-orders.show', $wo)->with('success', 'Work Order dibuat.');
    }

    public function show(WorkOrder $work_order)
    {
        return view('maintenance.wo.index', [
            'workOrder' => $work_order->load(['tasks', 'parts.item', 'technicians.employee', 'costs', 'equipment', 'asset']),
            'items' => WorkOrder::orderByDesc('id')->paginate(20),
            'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'IN_PROGRESS', 'COMPLETED', 'CLOSED', 'CANCELLED'],
        ]);
    }

    public function approve(WorkOrder $work_order)
    {
        if (! auth()->user()->hasPermission('work_order.approve')) {
            abort(403);
        }
        $work_order->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'MAINTENANCE', $work_order->id, WorkOrder::class);

        return back()->with('success', 'WO disetujui.');
    }

    public function start(WorkOrder $work_order)
    {
        if ($work_order->status !== 'APPROVED') {
            return back()->with('error', 'WO belum disetujui.');
        }
        $work_order->update(['status' => 'IN_PROGRESS', 'actual_start' => now()]);
        if ($work_order->equipment) {
            $work_order->equipment->update(['status' => 'MAINTENANCE']);
        }

        return back()->with('success', 'WO dimulai.');
    }

    public function complete(Request $request, WorkOrder $work_order)
    {
        if (! in_array($work_order->status, ['APPROVED', 'IN_PROGRESS'])) {
            return back()->with('error', 'WO harus disetujui/dimulai sebelum diselesaikan.');
        }
        $validated = $request->validate([
            'downtime_hours' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
        ]);

        $work_order->update([
            'status' => 'COMPLETED',
            'actual_finish' => now(),
            'downtime_hours' => $validated['downtime_hours'] ?? $work_order->downtime_hours,
        ]);
        $work_order->tasks()->update(['is_done' => true]);

        if (! empty($validated['labor_cost']) && $validated['labor_cost'] > 0) {
            $work_order->costs()->create(['cost_type' => 'LABOR', 'amount' => $validated['labor_cost']]);
        }
        $work_order->update(['actual_cost' => (float) $work_order->costs()->sum('amount')]);

        if ($work_order->equipment) {
            $work_order->equipment->update(['status' => 'AVAILABLE']);
        }

        AuditService::log('UPDATE', 'MAINTENANCE', $work_order->id, WorkOrder::class, null, ['status' => 'COMPLETED']);

        return back()->with('success', 'WO selesai.');
    }

    /**
     * Issue spare part: inventory out + maintenance cost + journal.
     */
    public function issuePart(WorkOrder $work_order, MaintenancePart $part)
    {
        try {
            MaintenanceService::issuePart($part);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sparepart diterbitkan: stok berkurang & biaya tercatat.');
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'asset_id' => 'nullable|exists:assets,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'type' => 'required|in:PREVENTIVE,CORRECTIVE,BREAKDOWN',
            'priority' => 'required|in:LOW,NORMAL,HIGH,URGENT',
            'date' => 'required|date',
            'planned_finish' => 'nullable|date',
            'description' => 'required|max:2000',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);
    }
}
