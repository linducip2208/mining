<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;

use App\Models\MiningActivity;
use App\Models\Site;
use App\Models\Pit;
use App\Models\Shift;
use App\Models\Equipment;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Company;
use App\Services\AuditService;
use App\Services\ApprovalService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MiningActivityController extends Controller
{
    use AppliesDataScope;

    public function dashboard(Request $request)
    {
        $date = $request->date ?? today()->toDateString();
        $siteId = $request->integer('site_id') ?: null;
        $this->ensureSiteInScope($siteId);
        $sites = auth()->user()?->accessibleSiteIds();

        $base = MiningActivity::whereDate('date', $date)
            ->when($siteId, fn ($q) => $q->where('mining_activities.site_id', $siteId))
            ->when($sites !== null, fn ($q) => $q->whereIn('mining_activities.site_id', $sites))
            ->whereIn('mining_activities.status', ['APPROVED', 'POSTED']);

        $tons = (float) (clone $base)->sum('tonnage');
        $trips = (clone $base)->count();
        $units = (clone $base)->distinct('equipment_id')->count('equipment_id');
        $byShift = (clone $base)->join('shifts', 'shifts.id', '=', 'mining_activities.shift_id')
            ->selectRaw('shifts.name, COALESCE(SUM(tonnage),0) tons, COUNT(*) trips')
            ->groupBy('shifts.name')->orderByDesc('tons')->get();
        $byPit = (clone $base)->join('pits', 'pits.id', '=', 'mining_activities.pit_id')
            ->selectRaw('pits.name, COALESCE(SUM(tonnage),0) tons, COUNT(*) trips')
            ->groupBy('pits.name')->orderByDesc('tons')->get();
        $latest = MiningActivity::with(['site', 'pit', 'shift', 'equipment'])
            ->when($siteId, fn ($q) => $q->where('mining_activities.site_id', $siteId))
            ->when($sites !== null, fn ($q) => $q->whereIn('mining_activities.site_id', $sites))
            ->orderByDesc('date')->orderByDesc('id')->limit(10)->get();
        $byStatus = MiningActivity::whereDate('date', $date)
            ->when($siteId, fn ($q) => $q->where('mining_activities.site_id', $siteId))
            ->when($sites !== null, fn ($q) => $q->whereIn('mining_activities.site_id', $sites))
            ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('mining.dashboard', [
            'date' => $date, 'tons' => $tons, 'trips' => $trips, 'units' => $units,
            'avgPayload' => $trips ? round($tons / $trips, 1) : 0,
            'byShift' => $byShift, 'byPit' => $byPit, 'latest' => $latest, 'byStatus' => $byStatus,
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function index(Request $request)
    {        $items = MiningActivity::with(['site', 'pit', 'shift', 'equipment', 'operator', 'item'])
            ->when($request->q, fn ($q) => $q->where('number', 'like', "%{$request->q}%"))
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from, fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('date', '<=', $request->to))
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->orderByDesc('date')->paginate(20)->withQueryString();

        return view('mining.index', [
            'items' => $items,
            'sites' => Site::pluck('name', 'id')->all(),
            'statuses' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'CANCELLED'],
        ]);
    }

    public function create()
    {
        return view('mining.form', $this->refs());
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $item = DB::transaction(function () use ($validated) {
            $validated['number'] = \App\Services\NumberingService::generate('MA', $validated['company_id']);
            $validated['status'] = 'DRAFT';
            $validated['created_by'] = auth()->id();
            return MiningActivity::create($validated);
        });
        AuditService::created('MINING', $item);
        return redirect()->route('mining-activities.index')->with('success', 'Aktivitas tambang berhasil dibuat.');
    }

    public function show(MiningActivity $mining_activity)
    {
        $mining_activity->load(['site', 'pit', 'shift', 'equipment', 'operator', 'item', 'haulings']);
        return view('mining.show', ['activity' => $mining_activity]);
    }

    public function edit(MiningActivity $mining_activity)
    {
        if ($mining_activity->status !== 'DRAFT') {
            return back()->with('error', 'Hanya status DRAFT yang dapat diubah.');
        }
        return view('mining.form', ['activity' => $mining_activity] + $this->refs());
    }

    public function update(Request $request, MiningActivity $mining_activity)
    {
        if ($mining_activity->status !== 'DRAFT') {
            return back()->with('error', 'Hanya status DRAFT yang dapat diubah.');
        }
        $validated = $this->validateInput($request);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $mining_activity->update($validated + ['updated_by' => auth()->id()]);
        AuditService::updated('MINING', $mining_activity);
        return redirect()->route('mining-activities.index')->with('success', 'Aktivitas berhasil diperbarui.');
    }

    public function destroy(MiningActivity $mining_activity)
    {
        if ($mining_activity->status !== 'DRAFT') {
            return back()->with('error', 'Hanya status DRAFT yang dapat dihapus.');
        }
        AuditService::deleted('MINING', $mining_activity);
        $mining_activity->delete();
        return redirect()->route('mining-activities.index')->with('success', 'Aktivitas dihapus.');
    }

    public function submit(MiningActivity $mining_activity)
    {
        if ($mining_activity->status !== 'DRAFT') {
            return back()->with('error', 'Status tidak valid.');
        }
        ApprovalService::submit('MINING', 'MINING_ACTIVITY', $mining_activity);
        return back()->with('success', 'Aktivitas diajukan untuk persetujuan.');
    }

    public function approve(MiningActivity $mining_activity)
    {
        if (!auth()->user()->hasPermission('mining.approve')) {
            abort(403);
        }
        if ($mining_activity->status !== 'SUBMITTED') {
            return back()->with('error', 'Status tidak valid.');
        }
        $mining_activity->update(['status' => 'APPROVED', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        AuditService::log('APPROVE', 'MINING', $mining_activity->id, MiningActivity::class, null, ['number' => $mining_activity->number]);
        return back()->with('success', 'Aktivitas disetujui.');
    }

    /**
     * Post: move mined material into source stockpile via STOCK LEDGER.
     */
    public function post(MiningActivity $mining_activity)
    {
        if (!auth()->user()->hasPermission('mining.post')) {
            abort(403);
        }
        if ($mining_activity->status !== 'APPROVED') {
            return back()->with('error', 'Aktivitas harus APPROVED sebelum posting.');
        }
        if (!$mining_activity->item_id || !$mining_activity->target_warehouse_id) {
            return back()->with('error', 'Material & stockpile tujuan wajib diisi untuk posting.');
        }

        try {
            DB::transaction(function () use ($mining_activity) {
                StockService::move(
                    $mining_activity->target_warehouse_id,
                    $mining_activity->item_id,
                    'PRODUCTION',
                    (float) $mining_activity->tonnage,
                    0,
                    $mining_activity->company_id,
                    $mining_activity->site_id,
                    $mining_activity->id,
                    'MINING_ACTIVITY',
                    $mining_activity->number,
                    null,
                    $mining_activity->date->toDateString()
                );
                $mining_activity->update(['status' => 'POSTED', 'posted_by' => auth()->id(), 'posted_at' => now()]);
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditService::log('POST', 'MINING', $mining_activity->id, MiningActivity::class, null, ['number' => $mining_activity->number, 'tonnage' => $mining_activity->tonnage]);
        return back()->with('success', 'Aktivitas diposting ke stok.');
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'required|exists:sites,id',
            'pit_id' => 'nullable|exists:pits,id',
            'date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'activity_type' => 'required|in:MINING,HAULING,CLEANING,DRILLING,OTHER',
            'equipment_id' => 'nullable|exists:equipment,id',
            'operator_id' => 'nullable|exists:employees,id',
            'item_id' => 'nullable|exists:items,id',
            'source_warehouse_id' => 'nullable|exists:warehouses,id',
            'target_warehouse_id' => 'nullable|exists:warehouses,id',
            'quantity' => 'nullable|numeric|min:0',
            'tonnage' => 'required|numeric|min:0.0001',
            'working_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|max:2000',
        ]);
    }

    protected function refs(): array
    {
        return [
            'activity' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'pits' => Pit::all(),
            'shifts' => Shift::all(),
            'equipments' => Equipment::where('status', '!=', 'RETIRED')->get(),
            'operators' => Employee::where('status', 'ACTIVE')->get(),
            'items' => Item::where('type', 'RAW')->orWhere('type', 'PRODUCT')->get(),
            'warehouses' => Warehouse::all(),
        ];
    }
}
