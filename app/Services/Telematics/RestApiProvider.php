<?php

namespace App\Services\Telematics;

use App\Models\TelematicsEvent;
use Illuminate\Support\Facades\Http;

/**
 * Generic REST pull driver. Config: endpoint, method, headers, mapping.
 * Vendor payloads are normalized via normalize() override per vendor
 * (extend this class) or the default pass-through mapping below.
 */
class RestApiProvider implements TelematicsProviderInterface
{
    public function __construct(protected array $config = [])
    {
    }

    public function code(): string
    {
        return 'REST_API';
    }

    public function sync(?int $providerId = null): int
    {
        $provider = $providerId ? \App\Models\TelematicsProvider::find($providerId) : null;
        $cfg = array_merge($this->config, $provider?->config ?? []);
        if (empty($cfg['endpoint'])) {
            throw new \DomainException('Endpoint provider belum dikonfigurasi.');
        }
        $resp = Http::timeout(20)
            ->withHeaders($cfg['headers'] ?? [])
            ->{strtolower($cfg['method'] ?? 'get')}($cfg['endpoint'], $cfg['params'] ?? []);
        if ($resp->failed()) {
            throw new \DomainException('Provider merespons ' . $resp->status());
        }
        $items = $cfg['data_key'] ? data_get($resp->json(), $cfg['data_key'], []) : $resp->json();
        $count = 0;
        foreach ((array) $items as $raw) {
            $event = $this->normalize((array) $raw);
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
        $map = $this->config['mapping'] ?? [];
        $pick = fn ($key, $default = null) => $map[$key] ? data_get($payload, $map[$key], $default) : ($payload[$key] ?? $default);
        $eq = \App\Models\Equipment::where('code', $pick('unit'))->first();
        return [
            'equipment_id' => $eq?->id,
            'external_unit_id' => $pick('unit'),
            'event_type' => strtoupper((string) ($pick('type', 'LOCATION'))),
            'event_time' => $pick('time', now()->toDateTimeString()),
            'latitude' => $pick('lat'),
            'longitude' => $pick('lng'),
            'speed_kph' => $pick('speed'),
            'ignition_on' => $pick('ignition'),
            'engine_hour' => $pick('hm'),
            'odometer_km' => $pick('km'),
            'fuel_percent' => $pick('fuel'),
            'geofence' => $pick('geofence'),
            'idle_minutes' => $pick('idle'),
            'raw' => $payload,
        ];
    }

    public static function drivers(): array
    {
        return [
            'SIMULATOR' => SimulatorProvider::class,
            'REST_API' => self::class,
        ];
    }

    public static function make(string $driver, array $config = []): TelematicsProviderInterface
    {
        $drivers = self::drivers();
        if (!isset($drivers[$driver])) {
            throw new \InvalidArgumentException('Driver telematics tidak dikenal: ' . $driver);
        }
        $class = $drivers[$driver];
        return $driver === 'REST_API' ? new $class($config) : new $class();
    }
}
