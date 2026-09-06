<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDataScope;
use App\Models\Weighbridge;
use App\Models\WeighbridgeDevice;
use App\Models\WeighbridgeReading;
use App\Services\AuditService;
use App\Services\Weighbridge\ManualProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WeighbridgeDeviceController extends Controller
{
    use AppliesDataScope;

    public function index(Request $request)
    {
        $devices = WeighbridgeDevice::with('weighbridge')->orderBy('code')->get();
        $readings = WeighbridgeReading::with(['device', 'weighbridge'])
            ->when($request->weighbridge_id, fn ($q) => $q->where('weighbridge_id', $request->weighbridge_id))
            ->orderByDesc('read_at')->limit(50)->get();
        return view('weighbridge.devices.index', [
            'devices' => $devices,
            'readings' => $readings,
            'weighbridges' => Weighbridge::all(),
            'drivers' => ['MANUAL' => 'Input Manual', 'SERIAL' => 'Serial/RS232', 'TCP' => 'TCP/IP', 'REST' => 'REST API (device push)'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'weighbridge_id' => 'required|exists:weighbridges,id',
            'code' => 'required|max:30|unique:weighbridge_devices,code',
            'name' => 'required|max:150',
            'driver' => 'required|in:MANUAL,SERIAL,TCP,REST',
            'endpoint' => 'nullable|max:255',
            'is_active' => 'boolean',
        ]);
        $device = WeighbridgeDevice::create([
            'weighbridge_id' => $validated['weighbridge_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'config' => !empty($validated['endpoint']) ? ['endpoint' => $validated['endpoint']] : [],
            'api_token' => $validated['driver'] === 'REST' ? Str::random(60) : null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);
        // validate driver contract eagerly
        ManualProvider::make($device->driver);
        AuditService::created('WEIGHBRIDGE', $device);
        return back()->with('success', 'Device tersimpan.' . ($device->api_token ? ' Token: ' . $device->api_token . ' (salin sekarang, disembunyikan setelahnya)' : ''));
    }

    public function live(WeighbridgeDevice $device)
    {
        $driver = ManualProvider::make($device->driver);
        return response()->json([
            'device' => $device->code,
            'driver' => $device->driver,
            'reading' => $driver->read($device->id),
            'frames_supported' => $device->driver === 'SERIAL' ? ['contoh: "=  12340 kg"', '"ST,GS, 12340kg"'] : [],
        ]);
    }
}
