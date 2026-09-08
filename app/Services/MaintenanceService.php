<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MaintenanceCost;
use App\Models\MaintenancePart;
use App\Models\MaintenanceSchedule;
use App\Models\StockReservation;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /**
     * Compute next due date for DAY/MONTH interval schedules.
     * RUNNING_HOUR/KM schedules are meter-driven (next_meter) and return null.
     */
    public static function computeNextDue(MaintenanceSchedule $schedule, ?string $baseDate = null): ?string
    {
        if (! in_array($schedule->interval_type, ['DAY', 'MONTH']) || (int) $schedule->interval_value <= 0) {
            return null;
        }
        $base = $baseDate ? Carbon::parse($baseDate) : ($schedule->last_done ? Carbon::parse($schedule->last_done) : now());

        return $schedule->interval_type === 'DAY'
            ? $base->copy()->addDays((int) $schedule->interval_value)->toDateString()
            : $base->copy()->addMonthsNoOverflow((int) $schedule->interval_value)->toDateString();
    }

    /**
     * Generate DRAFT work orders for due schedules (next_due <= today) that
     * have no open work order yet. Returns number of WOs created.
     */
    public static function generateDueWorkOrders(): int
    {
        $count = 0;
        $due = MaintenanceSchedule::where('is_active', true)
            ->whereNotNull('next_due')
            ->whereDate('next_due', '<=', today())
            ->with(['asset', 'equipment'])
            ->get();
        foreach ($due as $schedule) {
            $open = WorkOrder::where('maintenance_schedule_id', $schedule->id)
                ->whereNotIn('status', ['COMPLETED', 'CLOSED', 'CANCELLED'])
                ->exists();
            if ($open) {
                continue;
            }
            $companyId = $schedule->asset?->company_id ?? $schedule->equipment?->company_id;
            if (! $companyId) {
                continue;
            }
            DB::transaction(function () use ($schedule, $companyId, &$count) {
                $wo = WorkOrder::create([
                    'number' => NumberingService::generate('WO', $companyId),
                    'company_id' => $companyId,
                    'site_id' => $schedule->asset?->site_id ?? $schedule->equipment?->site_id,
                    'asset_id' => $schedule->asset_id,
                    'equipment_id' => $schedule->equipment_id,
                    'maintenance_schedule_id' => $schedule->id,
                    'type' => $schedule->type === 'CORRECTIVE' ? 'CORRECTIVE' : 'PREVENTIVE',
                    'date' => today()->toDateString(),
                    'description' => 'Otomatis dari jadwal: '.$schedule->name,
                    'status' => 'DRAFT',
                    'created_by' => auth()->id() ?? 1,
                ]);
                $advanced = self::computeNextDue($schedule, $schedule->next_due instanceof \DateTimeInterface
                    ? $schedule->next_due->format('Y-m-d')
                    : (string) $schedule->next_due);
                if ($advanced) {
                    $schedule->next_due = $advanced;
                    $schedule->save();
                }
                AuditService::log('CREATE', 'MAINTENANCE', $wo->id, WorkOrder::class, null, ['from_schedule' => $schedule->id]);
                $count++;
            });
        }

        return $count;
    }

    /**
    /**
     * Issue spare part for WO: inventory out + maintenance cost.
     */
    public static function issuePart(MaintenancePart $part): void
    {
        DB::transaction(function () use ($part) {
            if ($part->issue_status === 'ISSUED') {
                throw new \DomainException('Sparepart sudah diterbitkan.');
            }
            $wo = $part->workOrder;
            if (in_array($wo->status, ['COMPLETED', 'CLOSED', 'CANCELLED'])) {
                throw new \DomainException('Work order sudah '.$wo->status.' — sparepart tidak dapat diterbitkan.');
            }
            $warehouseId = $part->warehouse_id;
            if (! $warehouseId) {
                throw new \DomainException('Warehouse sparepart belum ditentukan.');
            }

            $unitCost = (float) $part->unit_cost ?: (float) Item::find($part->item_id)->avg_cost;
            $ledger = StockService::move(
                $warehouseId,
                $part->item_id,
                'MAINTENANCE_USAGE',
                0,
                (float) $part->qty,
                $wo->company_id,
                $wo->site_id,
                $wo->id,
                'WORK_ORDER',
                $wo->number,
                $unitCost,
                now()->toDateString()
            );

            $part->unit_cost = $unitCost;
            $part->total_cost = round($unitCost * $part->qty, 2);
            $part->issue_status = 'ISSUED';
            $part->stock_ledger_id = $ledger->id;
            $part->save();

            // mirror issue onto the WO reservation chain (reserve → issue → return → consumed)
            $reservation = StockReservation::where('ref_type', 'WORK_ORDER')->where('ref_id', $wo->id)
                ->where('item_id', $part->item_id)->where('warehouse_id', $warehouseId)
                ->where('status', 'RESERVED')->first();
            if ($reservation) {
                SparepartService::markIssued($reservation, (float) $part->qty, $unitCost);
                $part->requested_qty = $part->requested_qty ?: $part->qty;
                $part->reserved_qty = $part->reserved_qty ?: $part->qty;
                $part->save();
            }

            MaintenanceCost::create([
                'work_order_id' => $wo->id,
                'cost_type' => 'PART',
                'amount' => $part->total_cost,
                'notes' => 'Pemakaian sparepart',
            ]);

            $wo->actual_cost = (float) $wo->costs()->sum('amount');
            $wo->save();

            // Journal: Dr Maintenance Expense, Cr Inventory
            $journal = AccountingService::post($wo->company_id, now()->toDateString(), [
                ['code' => AccountingService::map('MAINTENANCE_EXPENSE'), 'debit' => $part->total_cost, 'memo' => 'Sparepart WO '.$wo->number],
                ['code' => AccountingService::map('INVENTORY_SPAREPART'), 'credit' => $part->total_cost, 'memo' => 'Pemakaian sparepart WO '.$wo->number],
            ], 'MAINTENANCE_PART', $wo->id, $wo->number, 'Issue sparepart '.$wo->number, 'MNT');
            MaintenanceCost::where('work_order_id', $wo->id)->where('cost_type', 'PART')->orderByDesc('id')->first()?->update(['journal_entry_id' => $journal->id]);

            AuditService::log('UPDATE', 'MAINTENANCE', $wo->id, WorkOrder::class, null, ['part_issued' => $part->item_id, 'qty' => $part->qty]);
        });
    }

    /**
     * Reverse the maintenance journal when issued parts are returned.
     * Dr Inventory Sparepart / Cr Maintenance Expense at the ORIGINAL issue cost.
     */
    public static function reversePartJournal(int $workOrderId, float $amount, string $workOrderNumber): void
    {
        if ($amount <= 0) {
            return;
        }
        AccountingService::post(
            WorkOrder::find($workOrderId)->company_id,
            now()->toDateString(),
            [
                ['code' => AccountingService::map('INVENTORY_SPAREPART'), 'debit' => $amount, 'memo' => 'Retur sparepart WO '.$workOrderNumber],
                ['code' => AccountingService::map('MAINTENANCE_EXPENSE'), 'credit' => $amount, 'memo' => 'Retur sparepart WO '.$workOrderNumber],
            ],
            'MAINTENANCE_PART_RETURN',
            $workOrderId,
            $workOrderNumber,
            'Reversal beban sparepart WO '.$workOrderNumber,
            'MNT'
        );
    }
}
