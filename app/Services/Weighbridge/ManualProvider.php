<?php

namespace App\Services\Weighbridge;

/**
 * Manual driver: no hardware — readings come from operator input.
 * Always "stable" because the operator confirms the value.
 */
class ManualProvider implements WeighbridgeProviderInterface
{
    public function code(): string
    {
        return 'MANUAL';
    }

    public function read(int $deviceId): ?array
    {
        return null; // no hardware to poll; operator inputs weight
    }

    public static function drivers(): array
    {
        return [
            'MANUAL' => self::class,
            'SERIAL' => SerialProvider::class,
            'TCP' => TcpProvider::class,
            'REST' => RestProvider::class,
        ];
    }

    public static function make(string $driver): WeighbridgeProviderInterface
    {
        $drivers = self::drivers();
        if (!isset($drivers[$driver])) {
            throw new \InvalidArgumentException('Driver timbangan tidak dikenal: ' . $driver);
        }
        return new $drivers[$driver]();
    }
}
