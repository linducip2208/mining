<?php

namespace App\Services;

use App\Models\Tire;
use App\Models\TireMovement;
use Illuminate\Support\Facades\DB;

/**
 * Tire lifecycle: NEW/STOCK -> INSTALLED -> (ROTATE/REPAIR) -> REMOVE -> STOCK/SCRAP.
 * Every transition writes a tire_movement row.
 */
class TireService
{
    public static function install(int $tireId, int $equipmentId, string $position, string $date, float $hm = 0, float $km = 0): TireMovement
    {
        return DB::transaction(function () use ($tireId, $equipmentId, $position, $date, $hm, $km) {
            $tire = Tire::lockForUpdate()->findOrFail($tireId);
            if ($tire->status === 'INSTALLED') {
                throw new \DomainException('Ban sedang terpasang — lepas dulu sebelum pasang ulang.');
            }
            if ($tire->status === 'SCRAP') {
                throw new \DomainException('Ban scrap tidak dapat dipasang.');
            }
            // satu slot hanya boleh ditempati satu ban aktif
            if (Tire::where('equipment_id', $equipmentId)
                ->where('position', $position)
                ->where('status', 'INSTALLED')
                ->exists()) {
                throw new \DomainException('Slot ' . $position . ' pada unit tersebut sudah ditempati ban lain.');
            }
            $tire->update([
                'status' => 'INSTALLED',
                'equipment_id' => $equipmentId,
                'position' => $position,
                'install_date' => $date,
                'install_hm' => $hm,
                'install_km' => $km,
            ]);
            $move = self::record($tire->id, $date, 'INSTALL', $equipmentId, $position, $hm, $km);
            AuditService::log('UPDATE', 'TIRE', $tire->id, Tire::class, null, ['installed' => $position]);
            return $move;
        });
    }

    public static function remove(int $tireId, string $date, ?string $reason = null, bool $toScrap = false, float $hm = 0, float $km = 0): TireMovement
    {
        return DB::transaction(function () use ($tireId, $date, $reason, $toScrap, $hm, $km) {
            $tire = Tire::lockForUpdate()->findOrFail($tireId);
            if ($tire->status !== 'INSTALLED') {
                throw new \DomainException('Hanya ban terpasang yang dapat dilepas.');
            }
            $move = self::record($tire->id, $date, 'REMOVE', $tire->equipment_id, $tire->position, $hm, $km, null, 0, $reason);
            $tire->update([
                'status' => $toScrap ? 'SCRAP' : 'STOCK',
                'equipment_id' => null,
                'position' => null,
            ]);
            if ($toScrap) {
                self::record($tire->id, $date, 'SCRAP', null, null, $hm, $km, null, 0, $reason);
            }
            AuditService::log('UPDATE', 'TIRE', $tire->id, Tire::class, null, ['removed' => $reason, 'scrap' => $toScrap]);
            return $move;
        });
    }

    public static function rotate(int $tireId, string $newPosition, string $date, float $hm = 0, float $km = 0): TireMovement
    {
        return DB::transaction(function () use ($tireId, $newPosition, $date, $hm, $km) {
            $tire = Tire::lockForUpdate()->findOrFail($tireId);
            if ($tire->status !== 'INSTALLED') {
                throw new \DomainException('Hanya ban terpasang yang dapat dirotasi.');
            }
            if (Tire::where('equipment_id', $tire->equipment_id)
                ->where('position', $newPosition)
                ->where('status', 'INSTALLED')
                ->where('id', '!=', $tire->id)
                ->exists()) {
                throw new \DomainException('Slot ' . $newPosition . ' sudah ditempati ban lain.');
            }
            $tire->update(['position' => $newPosition]);
            $move = self::record($tire->id, $date, 'ROTATE', $tire->equipment_id, $newPosition, $hm, $km);
            AuditService::log('UPDATE', 'TIRE', $tire->id, Tire::class, null, ['rotated_to' => $newPosition]);
            return $move;
        });
    }

    public static function repair(int $tireId, string $date, float $cost, ?string $notes = null): TireMovement
    {
        return DB::transaction(function () use ($tireId, $date, $cost, $notes) {
            $tire = Tire::lockForUpdate()->findOrFail($tireId);
            if ($tire->status === 'SCRAP') {
                throw new \DomainException('Ban scrap tidak dapat diperbaiki.');
            }
            $wasInstalled = $tire->status === 'INSTALLED';
            $tire->update(['status' => 'REPAIR']);
            $move = self::record($tire->id, $date, 'REPAIR', $wasInstalled ? $tire->equipment_id : null, $tire->position, 0, 0, null, $cost, $notes);
            // repair cost flows to maintenance cost pool via note; tire returns to previous state
            $tire->update(['status' => $wasInstalled ? 'INSTALLED' : 'STOCK']);
            AuditService::log('UPDATE', 'TIRE', $tire->id, Tire::class, null, ['repair_cost' => $cost]);
            return $move;
        });
    }

    protected static function record(int $tireId, string $date, string $type, ?int $equipmentId, ?string $position, float $hm, float $km, ?float $tread = null, float $cost = 0, ?string $reason = null): TireMovement
    {
        return TireMovement::create([
            'tire_id' => $tireId,
            'trx_date' => $date,
            'movement_type' => $type,
            'equipment_id' => $equipmentId,
            'position' => $position,
            'hm_reading' => $hm,
            'km_reading' => $km,
            'tread_depth' => $tread,
            'cost' => $cost,
            'reason' => $reason,
            'created_by' => auth()->id() ?? 1,
        ]);
    }
}
