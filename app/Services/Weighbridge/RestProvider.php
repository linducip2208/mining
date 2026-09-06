<?php

namespace App\Services\Weighbridge;

use App\Models\WeighbridgeDevice;
use App\Models\WeighbridgeReading;

/**
 * REST driver: external bridge devices PUSH readings to
 * POST /api/weighbridge/reading with the device api_token.
 * Idempotent via client-supplied idempotency key.
 */
class RestProvider implements WeighbridgeProviderInterface
{
    public function code(): string
    {
        return 'REST';
    }

    public function read(int $deviceId): ?array
    {
        $last = WeighbridgeReading::where('weighbridge_device_id', $deviceId)
            ->latest('read_at')
            ->first();
        if (!$last) {
            return null;
        }
        return [
            'raw' => (float) $last->raw_weight,
            'stable' => (float) $last->stable_weight,
            'at' => $last->read_at,
        ];
    }

    public static function ingest(string $token, array $payload): WeighbridgeReading
    {
        $device = WeighbridgeDevice::where('api_token', $token)->where('is_active', true)->first();
        if (!$device) {
            throw new \DomainException('Token device tidak valid.');
        }
        foreach (['raw_weight', 'stable_weight', 'read_at'] as $field) {
            if (!isset($payload[$field])) {
                throw new \InvalidArgumentException("Field {$field} wajib diisi.");
            }
        }
        // idempotency: same device + same read_at + same raw = one row
        $existing = WeighbridgeReading::where('weighbridge_device_id', $device->id)
            ->where('read_at', $payload['read_at'])
            ->where('raw_weight', $payload['raw_weight'])
            ->first();
        if ($existing) {
            return $existing;
        }
        $reading = WeighbridgeReading::create([
            'weighbridge_device_id' => $device->id,
            'weighbridge_id' => $device->weighbridge_id,
            'read_at' => $payload['read_at'],
            'raw_weight' => $payload['raw_weight'],
            'stable_weight' => $payload['stable_weight'],
            'is_stable' => (bool) ($payload['is_stable'] ?? false),
            'is_manual' => false,
            'notes' => $payload['notes'] ?? null,
        ]);
        $device->update(['last_seen_at' => now()]);
        return $reading;
    }
}
