<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Company;
use App\Models\DispatchTrip;
use App\Models\DumpingPoint;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\HaulingRoute;
use App\Models\LoadingPoint;
use App\Models\Pit;
use App\Models\Shift;
use App\Models\Site;
use App\Models\WeighbridgeTicket;
use App\Services\AuditService;
use App\Services\DispatchService;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    use AppliesDataScope;

    public function dashboard(Request $request)
    {
        $date = $request->date ?? today()->toDateString();
        $siteId = $request->integer('site_id') ?: null;
        $shiftId = $request->integer('shift_id') ?: null;

        $summary = $siteId
            ? DispatchService::shiftSummary($siteId, $date, $shiftId)
            : ['trips' => 0, 'tonnage' => 0, 'by_truck' => collect()];

        $active = DispatchTrip::with(['truck', 'driver', 'loader', 'loadingPoint', 'dumpingPoint'])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereDate('trip_date', $date)
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->orderBy('start_time')->get();

        return view('dispatch.dashboard', [
            'summary' => $summary,
            'active' => $active,
            'date' => $date,
            'sites' => Site::pluck('name', 'id')->all(),
            'shifts' => Shift::pluck('name', 'id')->all(),
        ]);
    }

    public function trips(Request $request)
    {
        $items = DispatchTrip::with(['truck', 'driver', 'loader', 'shift', 'ticket'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->site_id, fn ($q) => $q->where('site_id', $request->site_id))
            ->when($request->date, fn ($q) => $q->whereDate('trip_date', $request->date))
            ->when(!is_null($sites = auth()->user()?->accessibleSiteIds()), fn ($w) => $w->whereIn('site_id', $sites))
            ->orderByDesc('trip_date')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('dispatch.trips.index', [
            'items' => $items,
            'statuses' => ['PLANNED', 'LOADING', 'HAULING', 'DUMPED', 'COMPLETED', 'CANCELLED'],
            'sites' => Site::pluck('name', 'id')->all(),
        ]);
    }

    public function create()
    {
        return view('dispatch.trips.form', [
            'trip' => null,
            'companies' => Company::pluck('name', 'id')->all(),
            'sites' => Site::all(),
            'shifts' => Shift::all(),
            'trucks' => Equipment::whereIn('type', ['DUMP_TRUCK', 'TRUCK'])->orWhere('type', 'like', '%TRUCK%')->orderBy('code')->get(),
            'loaders' => Equipment::whereIn('type', ['EXCAVATOR', 'LOADER'])->orderBy('code')->get(),
            'drivers' => Employee::where('status', 'ACTIVE')->get(),
            'pits' => Pit::all(),
            'loadingPoints' => LoadingPoint::all(),
            'dumpingPoints' => DumpingPoint::all(),
            'routes' => HaulingRoute::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'required|exists:sites,id',
            'trip_date' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'truck_id' => 'nullable|exists:equipment,id',
            'driver_id' => 'nullable|exists:employees,id',
            'loader_id' => 'nullable|exists:equipment,id',
            'pit_id' => 'nullable|exists:pits,id',
            'loading_point_id' => 'nullable|exists:loading_points,id',
            'dumping_point_id' => 'nullable|exists:dumping_points,id',
            'hauling_route_id' => 'nullable|exists:hauling_routes,id',
            'notes' => 'nullable|max:2000',
        ]);
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
        $trip = DispatchService::assign($validated);
        return redirect()->route('dispatch.trips.show', $trip)->with('success', 'Trip dibuat: ' . $trip->number);
    }

    public function show(DispatchTrip $dispatch_trip)
    {
        return view('dispatch.trips.show', [
            'trip' => $dispatch_trip->load(['truck', 'driver', 'loader', 'shift', 'pit', 'loadingPoint', 'dumpingPoint', 'route', 'ticket']),
            'tickets' => WeighbridgeTicket::whereIn('status', ['COMPLETE', 'VALIDATED'])
                ->orderByDesc('id')->limit(50)->get(),
        ]);
    }

    public function stamp(Request $request, DispatchTrip $dispatch_trip)
    {
        $validated = $request->validate(['phase' => 'required|in:LOADING,HAULING,DUMPED,COMPLETED']);
        try {
            DispatchService::stamp($dispatch_trip, $validated['phase']);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Trip maju ke fase ' . $validated['phase'] . '.');
    }

    public function linkTicket(Request $request, DispatchTrip $dispatch_trip)
    {
        $validated = $request->validate(['weighbridge_ticket_id' => 'required|exists:weighbridge_tickets,id']);
        try {
            DispatchService::linkTicket($dispatch_trip, WeighbridgeTicket::find($validated['weighbridge_ticket_id']));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Tiket tertaut — tonase dari timbangan.');
    }

    public function cancel(Request $request, DispatchTrip $dispatch_trip)
    {
        $validated = $request->validate(['reason' => 'nullable|max:500']);
        try {
            DispatchService::cancel($dispatch_trip, $validated['reason'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Trip dibatalkan.');
    }
}
