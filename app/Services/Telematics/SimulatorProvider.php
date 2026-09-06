<?php

namespace App\Services\Telematics;

use App\Models\Equipment;
use App\Models\TelematicsEvent;

/**
 * Simulator driver: derives plausible telemetry from meter logs &
 * assignments so the pipeline works end-to-end without hardware.
 */
class SimulatorProvider implements TelematicsProviderInterface
{
    public function code(): string
    {
        return 'SIMULATOR';
    }

    public function sync(?int $providerId = null): int
    {
        $count = 0;
        $units = Equipment::whereNotIn('status', ['RETIRED', 'DISPOSED'])->limit(50)->get();
        foreach ($units as $u) {
            $event = $this->normalize([
                'unit' => $u->code,
                'equipment_id' => $u->id,
            ]);
            $event['telematics_provider_id'] = $providerId;
            TelematicsEvent::create($event);
            $count++;
        }
        if ($providerId) {
            \App\Models\TelematicsProvider::whereKey($providerId)->update(['last_sync_at' => now()]);
        }
        return $count;
    }

    public function normalize(array $payload): array
    {
        $eq = isset($payload['equipment_id'])
            ? Equipment::find($payload['equipment_id'])
            : Equipment::where('code', $payload['unit'] ?? null)->first();
        $base = abs(crc32((string) ($eq?->code ?? 'X')) + (int) now()->format('Hi'));
        return [
            'equipment_id' => $eq?->id,
            'external_unit_id' => $eq?->code,
            'event_type' => 'LOCATION',
            'event_time' => now(),
            'latitude' => -2.0 - (($base % 500) / 1000),
            'longitude' => 115.0 + (($base % 700) / 1000),
            'speed_kph' => $eq && $eq->status === 'IN_USE' ? ($base % 35) : 0,
            'ignition_on' => $eq && in_array($eq->status, ['IN_USE', 'IDLE']),
            'engine_hour' => $eq ? (float) $eq->meter_reading : null,
            'odometer_km' => $eq ? (float) $eq->odometer_km : null,
            'fuel_percent' => 30 + ($base % 65),
            'geofence' => $eq?->site?->code,
            'idle_minutes' => $eq && $eq->status === 'IDLE' ? 45 : 0,
            'raw' => ['simulated' => true, 'at' => now()->toIso8601String()],
        ];
    }
}
