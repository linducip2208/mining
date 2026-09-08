<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DispatchTrip;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\WeighbridgeTicket;
use Illuminate\Support\Facades\DB;

/**
 * Shift dispatch: assign fleet, track trip timestamps, link weighbridge.
 * Tonnage NEVER input manually when a ticket is linked (single source).
 */
class DispatchService
{
    private const UNUSABLE_TRUCK_STATUS = ['MAINTENANCE', 'BREAKDOWN', 'RETIRED', 'DISPOSED'];

    public static function assign(array $data): DispatchTrip
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['truck_id'])) {
                $truckStatus = Equipment::where('id', $data['truck_id'])->value('status');
                if (in_array($truckStatus, self::UNUSABLE_TRUCK_STATUS, true)) {
                    throw new \DomainException('Status truk '.$truckStatus.' — tidak dapat ditugaskan.');
                }
            }
            if (! empty($data['driver_id'])) {
                $driver = Employee::find($data['driver_id']);
                if ($driver && $driver->status !== 'ACTIVE') {
                    throw new \DomainException('Driver tidak aktif (status: '.$driver->status.') — tidak dapat ditugaskan.');
                }
            }
            // cegah double-assign: satu truk + shift + tanggal hanya boleh satu trip aktif.
            // tanpa shift, duplikasi dicek per truk + tanggal.
            if (! empty($data['truck_id']) && ! empty($data['trip_date'])) {
                $dup = DispatchTrip::where('truck_id', $data['truck_id'])
                    ->whereDate('trip_date', $data['trip_date'])
                    ->whereNotIn('status', ['CANCELLED'])
                    ->when(! empty($data['shift_id']), fn ($q) => $q->where('shift_id', $data['shift_id']))
                    ->exists();
                if ($dup) {
                    throw new \DomainException('Truk tersebut sudah memiliki trip pada shift & tanggal ini.');
                }
            }
            // satu driver tidak boleh ditugaskan dua kali pada tanggal yang sama
            if (! empty($data['driver_id']) && ! empty($data['trip_date'])) {
                $driverDup = DispatchTrip::where('driver_id', $data['driver_id'])
                    ->whereDate('trip_date', $data['trip_date'])
                    ->whereNotIn('status', ['CANCELLED'])
                    ->exists();
                if ($driverDup) {
                    throw new \DomainException('Driver tersebut sudah memiliki trip pada tanggal ini.');
                }
            }
            $trip = DispatchTrip::create([
                'number' => NumberingService::generate('DSP', $data['company_id'] ?? null),
                'company_id' => $data['company_id'],
                'site_id' => $data['site_id'],
                'trip_date' => $data['trip_date'],
                'shift_id' => $data['shift_id'] ?? null,
                'truck_id' => $data['truck_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'loader_id' => $data['loader_id'] ?? null,
                'pit_id' => $data['pit_id'] ?? null,
                'loading_point_id' => $data['loading_point_id'] ?? null,
                'dumping_point_id' => $data['dumping_point_id'] ?? null,
                'hauling_route_id' => $data['hauling_route_id'] ?? null,
                'status' => 'PLANNED',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id() ?? 1,
            ]);
            AuditService::created('DISPATCH', $trip);

            return $trip;
        });
    }

    /**
     * Advance trip phase with timestamp. Phases must progress in order.
     */
    public static function stamp(DispatchTrip $trip, string $phase): DispatchTrip
    {
        return DB::transaction(function () use ($trip, $phase) {
            $order = ['PLANNED' => 0, 'LOADING' => 1, 'HAULING' => 2, 'DUMPED' => 3, 'COMPLETED' => 4];
            $field = match ($phase) {
                'LOADING' => 'loading_start',
                'HAULING' => 'loading_finish',
                'DUMPED' => 'dump_time',
                'COMPLETED' => 'end_time',
                default => throw new \InvalidArgumentException('Fase tidak dikenal: '.$phase),
            };
            $trip = DispatchTrip::lockForUpdate()->find($trip->id);
            if ($trip->status === 'CANCELLED' || $trip->status === 'COMPLETED') {
                throw new \DomainException('Trip sudah selesai/dibatalkan.');
            }
            if (($order[$phase] ?? 0) <= ($order[$trip->status] ?? 0)) {
                throw new \DomainException('Fase harus maju berurutan.');
            }
            if ($trip->status === 'PLANNED' && ! $trip->start_time) {
                $trip->start_time = now();
            }
            $trip->update([$field => now(), 'status' => $phase, 'updated_by' => auth()->id() ?? 1]);
            AuditService::log('STAMP', 'DISPATCH', $trip->id, DispatchTrip::class, null, ['phase' => $phase, 'status' => $trip->status]);

            return $trip->fresh();
        });
    }

    /**
     * Link weighbridge ticket: tonnage flows FROM the ticket.
     * Idempotent: re-linking the same ticket is a no-op; another ticket is rejected.
     */
    public static function linkTicket(DispatchTrip $trip, WeighbridgeTicket $ticket): DispatchTrip
    {
        return DB::transaction(function () use ($trip, $ticket) {
            $trip = DispatchTrip::lockForUpdate()->find($trip->id);
            $ticket = WeighbridgeTicket::lockForUpdate()->find($ticket->id);
            if ($trip->weighbridge_ticket_id === $ticket->id) {
                return $trip;
            }
            if ($trip->weighbridge_ticket_id !== null) {
                throw new \DomainException('Trip sudah tertaut ke tiket lain.');
            }
            if (! in_array($ticket->status, ['COMPLETE', 'VALIDATED', 'POSTED'])) {
                throw new \DomainException('Tiket timbangan belum lengkap.');
            }
            if ($ticket->company_id !== null && $trip->company_id !== null && (int) $ticket->company_id !== (int) $trip->company_id) {
                throw new \DomainException('Tiket timbangan milik perusahaan lain.');
            }
            if ($ticket->site_id !== null && $trip->site_id !== null && (int) $ticket->site_id !== (int) $trip->site_id) {
                throw new \DomainException('Tiket timbangan milik situs lain.');
            }
            // satu tiket hanya boleh memberi tonnage ke satu konsumen — sudah
            // terpakai oleh DO (atau trip lain) berarti tonnage akan dihitung ganda
            if (DeliveryOrder::where('weighbridge_ticket_id', $ticket->id)->exists()) {
                throw new \DomainException('Tiket timbangan sudah dikonsumsi surat jalan (DO) — tidak dapat ditautkan ke trip.');
            }
            $trip->update([
                'weighbridge_ticket_id' => $ticket->id,
                'tonnage' => $ticket->net,
                'updated_by' => auth()->id() ?? 1,
            ]);
            AuditService::log('UPDATE', 'DISPATCH', $trip->id, DispatchTrip::class, null, ['ticket' => $ticket->ticket_no, 'tonnage' => $ticket->net]);

            return $trip->fresh();
        });
    }

    public static function cancel(DispatchTrip $trip, ?string $reason = null): void
    {
        if (in_array($trip->status, ['COMPLETED', 'CANCELLED'])) {
            throw new \DomainException('Trip sudah selesai/dibatalkan.');
        }
        $trip->update(['status' => 'CANCELLED', 'notes' => trim(($trip->notes ? $trip->notes.' ' : '').'[cancel: '.($reason ?? '-').']')]);
        AuditService::log('CANCEL', 'DISPATCH', $trip->id, DispatchTrip::class, null, null, $reason);
    }

    /**
     * Shift productivity summary: trips, tonnage, ton/hour per truck & loader.
     */
    public static function shiftSummary(int $siteId, string $date, ?int $shiftId = null): array
    {
        $trips = DispatchTrip::with(['truck', 'driver', 'loader'])
            ->where('site_id', $siteId)
            ->whereDate('trip_date', $date)
            ->when($shiftId, fn ($q) => $q->where('shift_id', $shiftId))
            ->whereNotIn('status', ['CANCELLED'])
            ->get();

        $totalTon = (float) $trips->sum('tonnage');
        $byTruck = $trips->groupBy('truck_id')->map(function ($g) {
            $first = $g->first();
            $hours = $g->sum(fn ($t) => ($t->cycleStats()['cycle_min'] ?? 0) / 60);

            return [
                'truck' => $first->truck?->code.' - '.$first->truck?->name,
                'driver' => $first->driver?->name,
                'trips' => $g->count(),
                'tonnage' => round((float) $g->sum('tonnage'), 2),
                'hours' => round($hours, 2),
                'ton_per_hour' => $hours > 0 ? round((float) $g->sum('tonnage') / $hours, 2) : 0,
            ];
        })->values();

        return [
            'trips' => $trips->count(),
            'tonnage' => round($totalTon, 2),
            'by_truck' => $byTruck,
        ];
    }
}
