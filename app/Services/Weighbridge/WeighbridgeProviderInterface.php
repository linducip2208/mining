<?php

namespace App\Services\Weighbridge;

use App\Models\WeighbridgeReading;

/**
 * Integration layer for weighbridge hardware.
 * Drivers: MANUAL (operator keyboard), SERIAL/TCP (polled daemons),
 * REST (external bridge devices push here).
 */
interface WeighbridgeProviderInterface
{
    public function code(): string;

    /** Latest reading snapshot or null when device unreachable. */
    public function read(int $deviceId): ?array;
}
