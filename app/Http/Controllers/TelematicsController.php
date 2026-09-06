<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Equipment;
use App\Models\TelematicsEvent;
use App\Models\TelematicsProvider;
use App\Services\AuditService;
use App\Services\Telematics\RestApiProvider;
use Illuminate\Http\Request;

class TelematicsController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $providers = TelematicsProvider::orderBy('code')->get();
        $events = TelematicsEvent::with(['equipment', 'provider'])
            ->when($request->equipment_id, fn ($q) => $q->where('equipment_id', $request->equipment_id))
            ->when($request->event_type, fn ($q) => $q->where('event_type', $request->event_type))
            ->orderByDesc('event_time')->paginate(25)->withQueryString();
        return view('telematics.index', [
            'providers' => $providers,
            'events' => $events,
            'units' => Equipment::orderBy('code')->get(),
            'types' => ['LOCATION', 'SPEED', 'IGNITION', 'ENGINE_HOUR', 'ODOMETER', 'FUEL_LEVEL', 'GEOFENCE', 'TRIP', 'IDLE'],
            'drivers' => ['SIMULATOR' => 'Simulator (tanpa hardware)', 'REST_API' => 'REST API (pull)'],
        ]);
    }

    public function storeProvider(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|max:30|unique:telematics_providers,code',
            'name' => 'required|max:150',
            'driver' => 'required|in:SIMULATOR,REST_API',
            'endpoint' => 'nullable|url',
            'is_active' => 'boolean',
        ]);
        $config = [];
        if (!empty($validated['endpoint'])) {
            $config['endpoint'] = $validated['endpoint'];
        }
        $provider = TelematicsProvider::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'config' => $config,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);
        AuditService::created('TELEMATICS', $provider);
        return back()->with('success', 'Provider tersimpan.');
    }

    public function sync(TelematicsProvider $provider)
    {
        try {
            $driver = RestApiProvider::make($provider->driver, $provider->config ?? []);
            $n = $driver->sync($provider->id);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', "Sinkronisasi selesai: {$n} event.");
    }
}
