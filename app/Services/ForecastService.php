<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\FuelIssue;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\PriceVariance;
use App\Models\ProductionBatch;
use App\Models\PurchaseOrder;
use App\Models\StockLedger;
use App\Models\WeighbridgeTicket;
use App\Models\WorkOrder;

/**
 * Forecasting (moving average + trend) & anomaly detection (z-score).
 * No ML dependencies — rules, moving average, standard deviation.
 */
class ForecastService
{
    public static function movingAverage(array $series, int $window = 7): float
    {
        $slice = array_slice(array_values(array_filter($series, fn ($v) => $v !== null)), -$window);
        if (empty($slice)) {
            return 0;
        }
        return round(array_sum($slice) / count($slice), 4);
    }

    public static function forecast(array $series, int $periods = 7, int $window = 7): array
    {
        $avg = self::movingAverage($series, $window);
        return array_fill(0, $periods, $avg);
    }

    public static function trend(array $series): string
    {
        $series = array_values($series);
        $n = count($series);
        if ($n < 4) {
            return 'FLAT';
        }
        $half = (int) floor($n / 2);
        $first = array_sum(array_slice($series, 0, $half)) / $half;
        $second = array_sum(array_slice($series, $half)) / ($n - $half);
        if ($second > $first * 1.1) {
            return 'UP';
        }
        if ($second < $first * 0.9) {
            return 'DOWN';
        }
        return 'FLAT';
    }

    /**
     * Z-score anomalies. Returns points with |z| >= threshold.
     */
    public static function anomalies(array $points, float $threshold = 2.5): array
    {
        $values = array_column($points, 'value');
        $n = count($values);
        if ($n < 5) {
            return [];
        }
        $mean = array_sum($values) / $n;
        $var = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $values)) / $n;
        $std = sqrt($var);
        if ($std <= 0) {
            return [];
        }
        $out = [];
        foreach ($points as $p) {
            $z = ($p['value'] - $mean) / $std;
            if (abs($z) >= $threshold) {
                $p['z'] = round($z, 2);
                $out[] = $p;
            }
        }
        return $out;
    }

    public static function productionForecast(int $days = 7): array
    {
        $daily = ProductionBatch::where('status', 'POSTED')
            ->whereDate('date', '>=', now()->subDays(27)->toDateString())
            ->selectRaw('DATE(date) d, SUM(net_output) t')
            ->groupBy('d')->orderBy('d')->pluck('t', 'd')->all();
        $values = array_map('floatval', array_values($daily));
        return [
            'history' => $daily,
            'daily_avg' => self::movingAverage($values),
            'trend' => self::trend($values),
            'forecast_next_7d' => round(array_sum(self::forecast($values, 7)), 2),
        ];
    }

    public static function fuelAnomalies(): array
    {
        $rows = FuelIssue::with('equipment')
            ->where('status', 'POSTED')
            ->whereDate('issue_date', '>=', now()->subDays(29)->toDateString())
            ->where('operating_hours', '>', 0)
            ->get()
            ->map(fn ($f) => [
                'label' => ($f->equipment?->code ?? '-') . ' ' . $f->issue_date->format('d/m') . ' (' . $f->number . ')',
                'value' => (float) $f->liter_per_hour,
            ])->all();
        return self::anomalies($rows);
    }

    public static function weighbridgeAnomalies(): array
    {
        $rows = WeighbridgeTicket::whereIn('status', ['COMPLETE', 'VALIDATED', 'POSTED'])
            ->whereDate('created_at', '>=', now()->subDays(29))
            ->get()->groupBy('item_id');
        $out = [];
        foreach ($rows as $itemId => $group) {
            $points = $group->map(fn ($t) => ['label' => $t->ticket_no, 'value' => (float) $t->net])->all();
            foreach (self::anomalies($points) as $a) {
                $a['item'] = Item::find($itemId)?->code;
                $out[] = $a;
            }
        }
        return $out;
    }

    public static function purchasePriceAnomalies(): array
    {
        $rows = PurchaseOrder::join('purchase_order_items', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->join('items', 'items.id', '=', 'purchase_order_items.item_id')
            ->whereIn('purchase_orders.status', ['APPROVED', 'COMPLETED'])
            ->whereDate('order_date', '>=', now()->subDays(89)->toDateString())
            ->selectRaw('items.code, purchase_order_items.unit_price as value, purchase_orders.number as label')
            ->get()->groupBy('code');
        $out = [];
        foreach ($rows as $code => $group) {
            foreach (self::anomalies($group->map(fn ($r) => ['label' => $r->label, 'value' => (float) $r->value])->all()) as $a) {
                $a['item'] = $code;
                $out[] = $a;
            }
        }
        return $out;
    }

    public static function overtimeAnomalies(): array
    {
        $rows = \App\Models\Overtime::with('employee')
            ->whereDate('date', '>=', now()->subDays(29)->toDateString())
            ->where('status', 'APPROVED')
            ->get()
            ->map(fn ($o) => ['label' => ($o->employee?->name ?? '-') . ' ' . $o->date->format('d/m'), 'value' => (float) $o->hours])
            ->all();
        return self::anomalies($rows);
    }

    public static function downtimeAnomalies(): array
    {
        $rows = WorkOrder::with('equipment')
            ->where('downtime_hours', '>', 0)
            ->whereDate('date', '>=', now()->subDays(89)->toDateString())
            ->get()
            ->map(fn ($w) => ['label' => ($w->equipment?->code ?? '-') . ' ' . $w->number, 'value' => (float) $w->downtime_hours])
            ->all();
        return self::anomalies($rows);
    }

    public static function stockAdjustmentAnomalies(): array
    {
        $rows = StockLedger::join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->whereIn('movement_type', ['ADJUSTMENT_PLUS', 'ADJUSTMENT_MINUS'])
            ->whereDate('trx_date', '>=', now()->subDays(89)->toDateString())
            ->selectRaw('items.code, ABS(SUM(qty_in - qty_out)) as value, stock_ledger.ref_number as label')
            ->groupBy('items.code', 'stock_ledger.ref_number')
            ->get()->map(fn ($r) => ['label' => $r->code . ' ' . $r->label, 'value' => (float) $r->value, 'item' => $r->code])
            ->all();
        return self::anomalies($rows);
    }

    public static function cashFlowForecast(int $days = 14): array
    {
        $in = \App\Models\Payment::where('type', 'RECEIVE')->where('status', 'POSTED')
            ->whereDate('payment_date', '>=', now()->subDays(27)->toDateString())
            ->selectRaw('DATE(payment_date) d, SUM(amount) t')->groupBy('d')->orderBy('d')->pluck('t')->map('floatval')->all();
        $avgIn = self::movingAverage($in);
        $out = \App\Models\Payment::where('type', 'PAY')->where('status', 'POSTED')
            ->whereDate('payment_date', '>=', now()->subDays(27)->toDateString())
            ->selectRaw('DATE(payment_date) d, SUM(amount) t')->groupBy('d')->orderBy('d')->pluck('t')->map('floatval')->all();
        $avgOut = self::movingAverage($out);
        return [
            'avg_daily_in' => $avgIn,
            'avg_daily_out' => $avgOut,
            'projected_net_14d' => round(($avgIn - $avgOut) * $days, 2),
            'trend_in' => self::trend($in),
            'trend_out' => self::trend($out),
        ];
    }
}
