<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\FuelIssue;
use App\Models\FuelLedger;
use App\Models\FuelReceipt;
use App\Models\FuelTank;
use App\Models\FuelTankDip;
use App\Models\FuelTransfer;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Fuel ledger is append-only source of truth.
 * Balance per tank = SUM(qty_in) - SUM(qty_out).
 */
class FuelService
{
    public static function tankBalance(int $tankId): float
    {
        return (float) FuelLedger::where('fuel_tank_id', $tankId)
            ->selectRaw('COALESCE(SUM(qty_in),0) - COALESCE(SUM(qty_out),0) as bal')
            ->value('bal');
    }

    public static function tankAvgCost(int $tankId): float
    {
        // Rata-rata hanya dari baris IN (penerimaan/transfer-in).
        // Baris OUT menyimpan total_cost untuk valuasi, tidak boleh ikut pembagi.
        $row = FuelLedger::where('fuel_tank_id', $tankId)
            ->where('qty_in', '>', 0)
            ->selectRaw('COALESCE(SUM(qty_in),0) q, COALESCE(SUM(total_cost),0) v')
            ->first();
        if (!$row || (float) $row->q <= 0) {
            return 0;
        }
        return round((float) $row->v / (float) $row->q, 2);
    }

    protected static function move(
        int $tankId, string $type, float $in, float $out,
        ?int $companyId, ?int $siteId, $refId, ?string $refType, ?string $refNumber,
        ?float $unitCost, string $date, ?string $notes = null
    ): FuelLedger {
        return DB::transaction(function () use ($tankId, $type, $in, $out, $companyId, $siteId, $refId, $refType, $refNumber, $unitCost, $date, $notes) {
            $tank = FuelTank::lockForUpdate()->find($tankId);
            if (!$tank) {
                throw new \InvalidArgumentException('Tangki BBM tidak ditemukan.');
            }
            if ($in < 0 || $out < 0 || ($in + $out) <= 0) {
                throw new \InvalidArgumentException('Liter mutasi BBM harus lebih dari 0.');
            }
            if ($out > 0 && self::tankBalance($tankId) < $out - 0.001) {
                throw new \DomainException('Stok BBM tangki ' . $tank->code . ' tidak cukup.');
            }
            return FuelLedger::create([
                'company_id' => $companyId ?? $tank->company_id,
                'site_id' => $siteId ?? $tank->site_id,
                'fuel_tank_id' => $tankId,
                'trx_date' => $date,
                'movement_type' => $type,
                'qty_in' => $in,
                'qty_out' => $out,
                'unit_cost' => $unitCost ?? 0,
                'total_cost' => $unitCost ? round($unitCost * max($in, $out), 2) : 0,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'ref_number' => $refNumber,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);
        });
    }

    public static function receive(FuelReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            $receipt = FuelReceipt::lockForUpdate()->findOrFail($receipt->id);
            if ($receipt->status === 'POSTED') {
                throw new \DomainException('Penerimaan sudah diposting.');
            }
            if ($receipt->status !== 'APPROVED') {
                throw new \DomainException('Penerimaan harus APPROVED sebelum posting.');
            }
            self::move(
                $receipt->fuel_tank_id, 'RECEIPT', (float) $receipt->liter, 0,
                $receipt->company_id, $receipt->site_id, $receipt->id, 'FUEL_RECEIPT',
                $receipt->number, (float) $receipt->unit_price, $receipt->receipt_date->toDateString()
            );

            $journal = AccountingService::post($receipt->company_id, $receipt->receipt_date->toDateString(), [
                ['code' => AccountingService::map('INVENTORY_FUEL'), 'debit' => (float) $receipt->total_cost, 'memo' => 'Pembelian BBM ' . $receipt->number],
                ['code' => AccountingService::map('AP_TRADE'), 'credit' => (float) $receipt->total_cost, 'memo' => 'Hutang BBM ' . ($receipt->supplier?->name ?? '')],
            ], 'FUEL_RECEIPT', $receipt->id, $receipt->number, 'Penerimaan BBM ' . $receipt->number, 'FUEL');

            $receipt->update(['status' => 'POSTED', 'journal_entry_id' => $journal->id]);
            AuditService::log('POST', 'FUEL', $receipt->id, FuelReceipt::class, null, ['receipt' => $receipt->number, 'liter' => $receipt->liter]);
        });
    }

    /**
     * Issue fuel to equipment: ledger OUT + L/H + variance vs standard + journal.
     * Idempotent via issue status.
     */
    public static function issue(FuelIssue $issue): void
    {
        DB::transaction(function () use ($issue) {
            $issue = FuelIssue::lockForUpdate()->findOrFail($issue->id);
            if ($issue->status === 'POSTED') {
                throw new \DomainException('Fuel issue sudah diposting.');
            }
            if ($issue->status !== 'APPROVED') {
                throw new \DomainException('Fuel issue harus APPROVED sebelum posting.');
            }
            if (!$issue->equipment_id && !$issue->vehicle_plate) {
                throw new \DomainException('Fuel issue wajib menunjuk unit atau plat kendaraan.');
            }
            if ($issue->hm_before !== null && $issue->hm_after !== null
                && (float) $issue->hm_after < (float) $issue->hm_before) {
                throw new \DomainException('HM akhir tidak boleh lebih kecil dari HM awal.');
            }

            $price = self::tankAvgCost($issue->fuel_tank_id);
            $cost = round((float) $issue->liter * $price, 2);

            self::move(
                $issue->fuel_tank_id, 'ISSUE', 0, (float) $issue->liter,
                $issue->company_id, $issue->site_id, $issue->id, 'FUEL_ISSUE',
                $issue->number, $price, $issue->issue_date->toDateString()
            );

            // consumption & variance
            $hours = max((float) $issue->hm_after - (float) $issue->hm_before, (float) $issue->operating_hours, 0.0001);
            $lph = round((float) $issue->liter / $hours, 3);
            $equipment = $issue->equipment_id ? Equipment::with('category')->find($issue->equipment_id) : null;
            $standard = (float) ($equipment?->category?->standard_fuel_lph ?? 0);
            $threshold = (float) ($equipment?->category?->fuel_warning_pct ?? 20);
            $variancePct = $standard > 0 ? round(($lph - $standard) / $standard * 100, 2) : 0;
            $status = 'NORMAL';
            if ($standard > 0 && $variancePct >= $threshold * 2) {
                $status = 'CRITICAL';
            } elseif ($standard > 0 && $variancePct >= $threshold) {
                $status = 'WARNING';
            }

            $journal = AccountingService::post($issue->company_id, $issue->issue_date->toDateString(), [
                ['code' => AccountingService::map('FUEL_EXPENSE'), 'debit' => $cost, 'memo' => 'BBM ' . ($equipment?->code ?? $issue->vehicle_plate) . ' ' . $issue->number],
                ['code' => AccountingService::map('INVENTORY_FUEL'), 'credit' => $cost, 'memo' => 'Pemakaian BBM ' . $issue->number],
            ], 'FUEL_ISSUE', $issue->id, $issue->number, 'Pemakaian BBM ' . $issue->number, 'FUEL');

            $issue->update([
                'fuel_price' => $price,
                'total_cost' => $cost,
                'operating_hours' => round($hours, 2),
                'liter_per_hour' => $lph,
                'variance_status' => $status,
                'status' => 'POSTED',
                'journal_entry_id' => $journal->id,
            ]);

            if (in_array($status, ['WARNING', 'CRITICAL'])) {
                $admins = \App\Models\User::where('status', 'ACTIVE')
                    ->whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN', 'SYSTEM_ADMIN', 'FLEET_MANAGER']))
                    ->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new SystemAlert(
                        'FUEL_ANOMALY',
                        'Anomali konsumsi BBM (' . $status . ')',
                        collect([(object) [
                            'id' => $issue->id,
                            'number' => $issue->number . ' — ' . ($equipment?->code ?? '-') . " {$lph} L/jam vs standar {$standard} (+{$variancePct}%)",
                        ]])
                    ));
                }
            }

            AuditService::log('POST', 'FUEL', $issue->id, FuelIssue::class, null, ['issue' => $issue->number, 'liter' => $issue->liter, 'lph' => $lph]);
        });
    }

    public static function transfer(FuelTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer = FuelTransfer::lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->status === 'POSTED') {
                throw new \DomainException('Transfer sudah diposting.');
            }
            if ($transfer->status === 'CANCELLED') {
                throw new \DomainException('Transfer yang dibatalkan tidak dapat diposting.');
            }
            if ($transfer->from_tank_id === $transfer->to_tank_id) {
                throw new \DomainException('Tangki asal dan tujuan harus berbeda.');
            }
            $from = FuelTank::find($transfer->from_tank_id);
            $to = FuelTank::find($transfer->to_tank_id);
            if (!$from || !$to) {
                throw new \InvalidArgumentException('Tangki asal/tujuan tidak ditemukan.');
            }
            if ((int) $from->company_id !== (int) $to->company_id) {
                throw new \DomainException('Transfer antar perusahaan tidak diizinkan (antar SBU gunakan dokumen jual-beli).');
            }
            $avg = self::tankAvgCost($from->id);

            self::move($from->id, 'TRANSFER_OUT', 0, (float) $transfer->liter, $from->company_id, $from->site_id, $transfer->id, 'FUEL_TRANSFER', $transfer->number, $avg, $transfer->transfer_date->toDateString());
            self::move($to->id, 'TRANSFER_IN', (float) $transfer->liter, 0, $to->company_id, $to->site_id, $transfer->id, 'FUEL_TRANSFER', $transfer->number, $avg, $transfer->transfer_date->toDateString());

            $transfer->update(['status' => 'POSTED']);
            AuditService::log('POST', 'FUEL', $transfer->id, FuelTransfer::class, null, ['number' => $transfer->number]);
        });
    }

    public static function dip(FuelTankDip $dip): FuelTankDip
    {
        $tank = FuelTank::find($dip->fuel_tank_id);
        if (!$tank) {
            throw new \InvalidArgumentException('Tangki BBM tidak ditemukan.');
        }
        // cegah dip ganda tangki+tanggal yang sama
        if (FuelTankDip::where('fuel_tank_id', $dip->fuel_tank_id)
            ->whereDate('dip_date', $dip->dip_date)
            ->when($dip->id, fn ($q) => $q->where('id', '!=', $dip->id))
            ->exists()) {
            throw new \DomainException('Dip tangki ini pada tanggal tersebut sudah tercatat.');
        }
        $dip->system_liter = round(self::tankBalance($dip->fuel_tank_id), 3);
        $dip->variance = round((float) $dip->physical_liter - $dip->system_liter, 3);
        $dip->variance_pct = $dip->system_liter != 0
            ? round($dip->variance / $dip->system_liter * 100, 3)
            : 0;
        $threshold = (float) \App\Models\Setting::get('fuel.dip_threshold_pct', 2);
        if (abs($dip->variance_pct) > $threshold) {
            $dip->status = 'PENDING';
            $dip->save();
            AuditService::log('CREATE', 'FUEL', $dip->id, FuelTankDip::class, null, [
                'tank' => $tank->code, 'variance' => $dip->variance, 'variance_pct' => $dip->variance_pct,
            ]);
            $approvers = \App\Models\User::where('status', 'ACTIVE')
                ->whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN', 'SYSTEM_ADMIN', 'FLEET_MANAGER', 'FUEL_ADMIN']))
                ->get();
            if ($approvers->isNotEmpty()) {
                Notification::send($approvers, new SystemAlert(
                    'FUEL_DIP_VARIANCE',
                    'Selisih dip tangki melewati threshold',
                    collect([(object) [
                        'id' => $dip->id,
                        'number' => $tank->code . ' ' . $dip->dip_date . ' — selisih ' . number_format($dip->variance, 1) . ' L (' . $dip->variance_pct . '%)',
                    ]])
                ));
            }
        } else {
            $dip->status = 'NORMAL';
            $dip->save();
            AuditService::log('CREATE', 'FUEL', $dip->id, FuelTankDip::class, null, [
                'tank' => $tank->code, 'variance' => $dip->variance,
            ]);
        }
        return $dip;
    }

    public static function approveDip(FuelTankDip $dip): void
    {
        if ($dip->status !== 'PENDING') {
            throw new \DomainException('Hanya dip PENDING yang dapat di-approve.');
        }
        $dip->update(['status' => 'APPROVED', 'approved_by' => auth()->id()]);
        AuditService::log('APPROVE', 'FUEL', $dip->id, FuelTankDip::class, null, ['variance_pct' => $dip->variance_pct]);
    }

    public static function consumptionReport(?int $companyId, ?int $siteId, string $from, string $to)
    {
        return FuelIssue::with(['equipment.category', 'tank'])
            ->where('status', 'POSTED')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereBetween('issue_date', [$from, $to])
            ->orderByDesc('issue_date')
            ->get();
    }
}
