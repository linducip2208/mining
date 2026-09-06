<?php

namespace App\Services;

use App\Models\Stockpile;
use App\Models\StockpileMovement;
use App\Models\StockpileSurvey;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Stockpile ledger + survey reconciliation.
 * System = Opening + ProductionIN + TransferIN − SalesOUT − TransferOUT ± Adjustment.
 */
class StockpileService
{
    public static function balance(int $stockpileId): float
    {
        return (float) StockpileMovement::where('stockpile_id', $stockpileId)
            ->selectRaw('COALESCE(SUM(qty_in),0) - COALESCE(SUM(qty_out),0) as bal')
            ->value('bal');
    }

    public static function move(int $stockpileId, string $type, float $in, float $out, $refId = null, ?string $refType = null, ?string $refNumber = null, ?string $date = null, ?string $notes = null): StockpileMovement
    {
        return DB::transaction(function () use ($stockpileId, $type, $in, $out, $refId, $refType, $refNumber, $date, $notes) {
            $pile = Stockpile::lockForUpdate()->find($stockpileId);
            if (!$pile) {
                throw new \InvalidArgumentException('Stockpile tidak ditemukan.');
            }
            if (!in_array($type, ['OPENING', 'PRODUCTION_IN', 'TRANSFER_IN', 'TRANSFER_OUT', 'SALES_OUT', 'ADJUSTMENT_PLUS', 'ADJUSTMENT_MINUS', 'SURVEY_ADJUSTMENT'])) {
                throw new \InvalidArgumentException('Tipe pergerakan tidak dikenal: ' . $type);
            }
            if ($out > 0 && self::balance($stockpileId) < $out - 0.0001) {
                throw new \DomainException('Saldo stockpile ' . $pile->code . ' tidak cukup.');
            }
            $move = StockpileMovement::create([
                'stockpile_id' => $stockpileId,
                'trx_date' => $date ?? now()->toDateString(),
                'movement_type' => $type,
                'qty_in' => $in,
                'qty_out' => $out,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'ref_number' => $refNumber,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);
            AuditService::log('POST', 'STOCKPILE', $move->id, StockpileMovement::class, null, ['pile' => $pile->code, 'type' => $type, 'in' => $in, 'out' => $out]);
            return $move;
        });
    }

    /**
     * Post ke pile yang terhubung ke gudang+item (jembatan warehouse ↔ stockpile).
     * No-op bila site tidak memakai stockpile (warehouse-only) — ledger gudang tetap sumber.
     */
    public static function moveForWarehouse(int $warehouseId, int $itemId, string $type, float $in, float $out, $refId = null, ?string $refType = null, ?string $refNumber = null, ?string $date = null, ?string $notes = null): ?StockpileMovement
    {
        $pile = Stockpile::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('status', true)
            ->orderBy('id')
            ->first();
        if (!$pile) {
            return null;
        }
        return self::move($pile->id, $type, $in, $out, $refId, $refType, $refNumber, $date, $notes);
    }

    /**
     * Record survey: variance = survey − system. Over threshold → INVESTIGATE + alert.
     */
    public static function survey(int $stockpileId, string $date, float $surveyBalance, ?string $surveyor = null): StockpileSurvey
    {
        return DB::transaction(function () use ($stockpileId, $date, $surveyBalance, $surveyor) {
            $pile = Stockpile::findOrFail($stockpileId);
            $system = round(self::balance($stockpileId), 4);
            $variance = round($surveyBalance - $system, 4);
            $pct = abs($system) > 0.0001 ? round($variance / $system * 100, 3) : ($variance != 0 ? 100 : 0);
            $threshold = (float) $pile->survey_threshold_pct;

            $survey = StockpileSurvey::create([
                'stockpile_id' => $stockpileId,
                'survey_date' => $date,
                'survey_balance' => $surveyBalance,
                'system_balance' => $system,
                'variance' => $variance,
                'variance_pct' => $pct,
                'status' => abs($pct) > $threshold ? 'INVESTIGATE' : 'PENDING',
                'surveyor' => $surveyor,
                'created_by' => auth()->id() ?? 1,
            ]);

            if (abs($pct) > $threshold) {
                $admins = \App\Models\User::where('status', 'ACTIVE')
                    ->whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN', 'SYSTEM_ADMIN', 'MINE_MANAGER', 'SITE_MANAGER']))
                    ->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new SystemAlert(
                        'STOCK_VARIANCE',
                        'Variansi survei stockpile melewati threshold',
                        collect([(object) ['id' => $survey->id, 'number' => $pile->code . " var {$variance} ({$pct}%)"]])
                    ));
                }
            }

            AuditService::created('STOCKPILE', $survey);
            return $survey;
        });
    }

    /**
     * Approve reconciliation: posts ADJUSTMENT to align system with survey.
     * Requires approval permission (checked by caller) + investigation note when over threshold.
     */
    public static function approveSurvey(StockpileSurvey $survey, ?string $investigation = null): void
    {
        DB::transaction(function () use ($survey, $investigation) {
            if (!in_array($survey->status, ['PENDING', 'INVESTIGATE'])) {
                throw new \DomainException('Survei tidak dapat di-approve pada status ' . $survey->status);
            }
            $pile = $survey->stockpile;
            if ($survey->status === 'INVESTIGATE' && empty($investigation) && empty($survey->investigation)) {
                throw new \DomainException('Variansi di atas threshold wajib diisi hasil investigasi.');
            }
            // rebase: hitung ulang selisih terhadap saldo TERKINI (mutasi antara
            // survei ↔ approve ikut diperhitungkan, ledger tidak pernah misalign)
            $variance = round((float) $survey->survey_balance - self::balance($pile->id), 4);
            if (abs($variance) > 0.0001) {
                self::move(
                    $pile->id, 'SURVEY_ADJUSTMENT',
                    $variance > 0 ? $variance : 0,
                    $variance < 0 ? abs($variance) : 0,
                    $survey->id, 'STOCKPILE_SURVEY', 'SURV-' . $survey->id,
                    $survey->survey_date->toDateString(), 'Rekonsiliasi survei'
                );
            }
            $survey->update([
                'status' => 'APPROVED',
                'investigation' => $investigation ?? $survey->investigation,
                'approved_by' => auth()->id() ?? 1,
            ]);
            // close immediately after posting adjustment
            $survey->update(['status' => 'CLOSED']);
            AuditService::log('APPROVE', 'STOCKPILE', $survey->id, StockpileSurvey::class, null, ['variance' => $variance]);
        });
    }
}
