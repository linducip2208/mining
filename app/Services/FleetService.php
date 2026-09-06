<?php

namespace App\Services;

use App\Models\Downtime;
use App\Models\Equipment;
use App\Models\EquipmentInspection;
use App\Models\EquipmentMeterLog;
use App\Models\MaintenanceCost;
use Illuminate\Support\Facades\DB;

/**
 * Fleet KPI & operations.
 *
 * PA (Physical Availability) = (Calendar − AllDowntime) / Calendar
 * MA (Mechanical Availability) = (Calendar − BreakdownDowntime) / Calendar
 * Utilization = OperatingHours / AvailableHours
 */
class FleetService
{
    public static function calendarHours(string $from, string $to): float
    {
        $days = (int) (\Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1);
        return max($days, 0) * 24;
    }

    public static function kpis(int $equipmentId, string $from, string $to): array
    {
        $calendar = self::calendarHours($from, $to);

        $downtime = (float) Downtime::where('equipment_id', $equipmentId)
            ->whereBetween('start_time', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->sum('hours');

        $breakdown = (float) \App\Models\WorkOrder::where('equipment_id', $equipmentId)
            ->where('type', 'BREAKDOWN')
            ->whereBetween('date', [$from, $to])
            ->sum('downtime_hours');

        $meters = EquipmentMeterLog::where('equipment_id', $equipmentId)
            ->whereBetween('log_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(operating_hours),0) op, COALESCE(SUM(idle_hours),0) idle')
            ->first();

        $operating = (float) ($meters->op ?? 0);
        if ($operating <= 0) {
            $operating = (float) \App\Models\EquipmentAssignment::where('equipment_id', $equipmentId)
                ->whereBetween('date', [$from, $to])
                ->sum('working_hours');
        }
        $idle = (float) ($meters->idle ?? 0);
        $available = max($calendar - $downtime, 0);

        // costs
        $fuelCost = (float) \App\Models\FuelIssue::where('equipment_id', $equipmentId)
            ->whereBetween('issue_date', [$from, $to])
            ->sum('total_cost');
        $maintCost = (float) MaintenanceCost::whereHas('workOrder', fn ($q) => $q->where('equipment_id', $equipmentId))
            ->whereHas('workOrder', fn ($q) => $q->whereBetween('date', [$from, $to]))
            ->sum('amount');
        $depr = self::depreciationForPeriod($equipmentId, $from, $to);
        $totalCost = $fuelCost + $maintCost + $depr;

        return [
            'calendar_hours' => round($calendar, 2),
            'downtime_hours' => round($downtime, 2),
            'breakdown_hours' => round($breakdown, 2),
            'operating_hours' => round($operating, 2),
            'idle_hours' => round($idle, 2),
            'available_hours' => round($available, 2),
            'pa_pct' => $calendar > 0 ? round($available / $calendar * 100, 2) : 0,
            'ma_pct' => $calendar > 0 ? round(max($calendar - $breakdown, 0) / $calendar * 100, 2) : 0,
            'utilization_pct' => $available > 0 ? round($operating / $available * 100, 2) : 0,
            'fuel_cost' => round($fuelCost, 2),
            'maintenance_cost' => round($maintCost, 2),
            'depreciation' => round($depr, 2),
            'total_cost' => round($totalCost, 2),
            'cost_per_hour' => $operating > 0 ? round($totalCost / $operating, 2) : 0,
        ];
    }

    /**
     * Straight-line depreciation pro-rata by days in period.
     */
    public static function depreciationForPeriod(int $equipmentId, string $from, string $to): float
    {
        $eq = Equipment::find($equipmentId);
        if (!$eq || (float) $eq->purchase_cost <= 0 || (int) $eq->useful_life_years <= 0) {
            return 0;
        }
        $days = (int) (\Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1);
        return round((float) $eq->purchase_cost / ((int) $eq->useful_life_years * 365) * $days, 2);
    }

    public static function fleetSummary(?int $companyId, ?int $siteId, string $from, string $to): array
    {
        $units = Equipment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->get();

        $rows = [];
        $totals = ['operating' => 0, 'downtime' => 0, 'breakdown' => 0, 'cost' => 0, 'units' => $units->count()];
        $byStatus = [];
        foreach ($units as $u) {
            $k = self::kpis($u->id, $from, $to);
            $rows[] = ['unit' => $u, 'kpi' => $k];
            $totals['operating'] += $k['operating_hours'];
            $totals['downtime'] += $k['downtime_hours'];
            $totals['breakdown'] += $k['breakdown_hours'];
            $totals['cost'] += $k['total_cost'];
            $byStatus[$u->status] = ($byStatus[$u->status] ?? 0) + 1;
        }
        $calendar = self::calendarHours($from, $to) * max($units->count(), 1);
        $totals['pa_pct'] = $calendar > 0 ? round(max($calendar - $totals['downtime'], 0) / $calendar * 100, 2) : 0;
        $totals['utilization_pct'] = max($calendar - $totals['downtime'], 0) > 0
            ? round($totals['operating'] / max($calendar - $totals['downtime'], 1) * 100, 2) : 0;

        return ['rows' => $rows, 'totals' => $totals, 'byStatus' => $byStatus];
    }

    public static function recordMeterLog(array $data): EquipmentMeterLog
    {
        return DB::transaction(function () use ($data) {
            $hmEnd = (float) ($data['hm_end'] ?? 0);
            $hmStart = (float) ($data['hm_start'] ?? 0);
            $kmEnd = (float) ($data['km_end'] ?? 0);
            $kmStart = (float) ($data['km_start'] ?? 0);
            if ($hmEnd < $hmStart - 0.001 || $kmEnd < $kmStart - 0.001) {
                throw new \DomainException('HM/KM akhir tidak boleh lebih kecil dari awal.');
            }
            $log = EquipmentMeterLog::create([
                'equipment_id' => $data['equipment_id'],
                'log_date' => $data['log_date'],
                'shift_id' => $data['shift_id'] ?? null,
                'hm_start' => $hmStart,
                'hm_end' => $hmEnd,
                'km_start' => $kmStart,
                'km_end' => $kmEnd,
                'operating_hours' => $data['operating_hours'] ?? round($hmEnd - $hmStart, 2),
                'idle_hours' => $data['idle_hours'] ?? 0,
                'status' => $data['status'] ?? 'IN_USE',
                'operator_id' => $data['operator_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id() ?? 1,
            ]);

            // roll current readings forward (never backwards)
            $eq = Equipment::lockForUpdate()->find($data['equipment_id']);
            if ($eq) {
                $eq->meter_reading = max((float) $eq->meter_reading, $hmEnd);
                $eq->odometer_km = max((float) $eq->odometer_km, $kmEnd);
                $eq->save();
            }

            AuditService::created('FLEET', $log);
            return $log;
        });
    }

    public static function recordInspection(array $data): EquipmentInspection
    {
        return DB::transaction(function () use ($data) {
            $inspection = EquipmentInspection::create([
                'equipment_id' => $data['equipment_id'],
                'inspection_date' => $data['inspection_date'],
                'shift_id' => $data['shift_id'] ?? null,
                'inspector_id' => $data['inspector_id'] ?? auth()->id(),
                'checklist' => $data['checklist'] ?? [],
                'result' => $data['result'] ?? 'PASS',
                'findings' => $data['findings'] ?? null,
                'created_by' => auth()->id() ?? 1,
            ]);

            // FAIL inspection forces BREAKDOWN status + downtime start
            if (($data['result'] ?? 'PASS') === 'FAIL') {
                $eq = Equipment::find($data['equipment_id']);
                if ($eq && !in_array($eq->status, ['BREAKDOWN', 'RETIRED', 'DISPOSED'])) {
                    $eq->status = 'BREAKDOWN';
                    $eq->save();
                }
            }

            AuditService::created('FLEET', $inspection);
            return $inspection;
        });
    }
}
