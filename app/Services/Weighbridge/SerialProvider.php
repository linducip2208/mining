<?php

namespace App\Services\Weighbridge;

/**
 * Serial/RS232 driver contract. Actual byte-level polling runs in a
 * dedicated daemon/worker (php artisan weighbridge:poll) using the
 * device config (port, baud, parity). This class defines the frame
 * parsing contract shared by the daemon.
 */
class SerialProvider implements WeighbridgeProviderInterface
{
    public function code(): string
    {
        return 'SERIAL';
    }

    public function read(int $deviceId): ?array
    {
        $device = \App\Models\WeighbridgeDevice::find($deviceId);
        if (!$device) {
            return null;
        }
        // Return the latest stable reading already ingested by the daemon.
        $last = \App\Models\WeighbridgeReading::where('weighbridge_device_id', $deviceId)
            ->where('is_stable', true)
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

    /**
     * Parse one indicator frame, e.g. "=  12340 kg\r\n" or "ST,GS, 12340kg".
     * Returns [raw, stable] or null when frame carries no weight.
     */
    public static function parseFrame(string $frame): ?array
    {
        if (!preg_match('/(\d[\d\s]*\.?\d*)\s*(kg|t)?/i', $frame, $m)) {
            return null;
        }
        $value = (float) str_replace(' ', '', $m[1]);
        if (stripos($frame, ' t') !== false && stripos($frame, 'kg') === false) {
            $value *= 1000;
        }
        $stable = (bool) preg_match('/\b(ST|stable)\b/i', $frame);
        return ['raw' => $value, 'stable' => $stable];
    }
}
