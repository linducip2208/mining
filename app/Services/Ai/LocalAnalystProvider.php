<?php

namespace App\Services\Ai;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\FuelIssue;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\MiningActivity;
use App\Models\ProductionBatch;
use App\Models\Site;
use App\Models\StockLedger;
use App\Models\WorkOrder;

/**
 * Local analyst: answers common questions from REAL queries only.
 * No external calls, no writes. Used when no LLM provider is configured.
 */
class LocalAnalystProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'LOCAL';
    }

    public function ask(string $question, string $context): array
    {
        $q = mb_strtolower($question);
        $answer = '';
        $sources = [];

        if (str_contains($q, 'cost') && (str_contains($q, 'ton') || str_contains($q, 'naik'))) {
            $answer = $this->costPerTonWhy($sources);
        } elseif (str_contains($q, 'downtime')) {
            $answer = $this->topDowntime($sources);
        } elseif ((str_contains($q, 'solar') || str_contains($q, 'bbm') || str_contains($q, 'fuel')) && (str_contains($q, 'abnormal') || str_contains($q, 'anomali') || str_contains($q, 'boros'))) {
            $answer = $this->fuelAnomaly($sources);
        } elseif (str_contains($q, 'overdue') || str_contains($q, 'piutang') || str_contains($q, 'telat bayar')) {
            $answer = $this->topOverdue($sources);
        } elseif (str_contains($q, 'stok') && (str_contains($q, 'akhir') || str_contains($q, 'estimasi') || str_contains($q, 'forecast') || str_contains($q, 'prediksi'))) {
            $answer = $this->stockForecast($sources);
        } elseif ((str_contains($q, 'banding') || str_contains($q, 'vs') || str_contains($q, 'komparasi')) && str_contains($q, 'site')) {
            $answer = $this->compareSites($sources);
        } elseif (str_contains($q, 'produksi') && (str_contains($q, 'prediksi') || str_contains($q, 'forecast') || str_contains($q, 'estimasi'))) {
            $answer = $this->productionForecast($sources);
        } else {
            $answer = "Saya bisa menjawab dari data sistem untuk topik: cost per ton, downtime alat, anomali solar, piutang overdue, estimasi stok, perbandingan site, dan prediksi produksi. Contoh: \"Alat mana yang downtime paling tinggi?\"";
        }

        return ['answer' => $answer, 'model' => 'local-analyst'];
    }

    protected function costPerTonWhy(array &$sources): string
    {
        $from = now()->subDays(13)->toDateString();
        $to = now()->toDateString();
        $rows = ProductionBatch::where('status', 'POSTED')
            ->whereBetween('date', [$from, $to])
            ->selectRaw('site_id, COALESCE(SUM(net_output),0) tons')
            ->groupBy('site_id')->pluck('tons', 'site_id');
        $sources[] = 'production_batches(POSTED, 14 hari)';
        if ($rows->isEmpty()) {
            return 'Belum ada produksi POSTED 14 hari terakhir, sehingga cost/ton tidak dapat dihitung.';
        }
        $worst = $rows->sort()->keys()->first();
        $site = Site::find($worst);
        $fuel = (float) FuelIssue::where('status', 'POSTED')->when($worst, fn ($q) => $q->where('site_id', $worst))->whereBetween('issue_date', [$from, $to])->sum('total_cost');
        $sources[] = 'fuel_issues(POSTED)';
        return "Site dengan tonase terkecil 14 hari terakhir: " . ($site?->name ?? '-') . " (" . number_format($rows[$worst], 2) . " ton). "
            . "Cost/ton naik ketika pembagi (ton saleable) kecil sementara biaya tetap (BBM site ini Rp " . number_format($fuel, 0, ',', '.') . ") tidak turun proporsional. "
            . "Lihat rincian di Cost Engine per site/periode.";
    }

    protected function topDowntime(array &$sources): string
    {
        $rows = WorkOrder::with('equipment')
            ->where('downtime_hours', '>', 0)
            ->orderByDesc('downtime_hours')->limit(5)->get();
        $sources[] = 'work_orders(downtime_hours)';
        if ($rows->isEmpty()) {
            return 'Tidak ada downtime tercatat pada work order.';
        }
        $lines = $rows->map(fn ($w) => ($w->equipment?->code ?? $w->asset?->name ?? '-') . ': ' . $w->downtime_hours . ' jam (' . $w->number . ')');
        return "Downtime tertinggi:\n- " . $lines->implode("\n- ");
    }

    protected function fuelAnomaly(array &$sources): string
    {
        $rows = FuelIssue::with('equipment.category')->where('status', 'POSTED')
            ->whereIn('variance_status', ['WARNING', 'CRITICAL'])
            ->orderByDesc('issue_date')->limit(5)->get();
        $sources[] = 'fuel_issues(variance WARNING/CRITICAL)';
        if ($rows->isEmpty()) {
            return 'Tidak ada anomali konsumsi BBM (semua dalam threshold standar per model alat).';
        }
        $lines = $rows->map(fn ($f) => ($f->equipment?->code ?? '-') . ': ' . $f->liter_per_hour . ' L/jam [' . $f->variance_status . '] (' . $f->number . ')');
        return "Konsumsi abnormal terdeteksi:\n- " . $lines->implode("\n- ");
    }

    protected function topOverdue(array &$sources): string
    {
        $rows = Invoice::with('customer')->whereIn('status', ['POSTED', 'PARTIALLY_PAID'])
            ->whereDate('due_date', '<', now())->orderBy('due_date')->limit(5)->get();
        $sources[] = 'invoices(overdue)';
        if ($rows->isEmpty()) {
            return 'Tidak ada faktur overdue saat ini.';
        }
        $lines = $rows->map(fn ($i) => ($i->customer?->name ?? '-') . ': Rp ' . number_format($i->total - $i->paid_amount, 0, ',', '.') . ' (' . $i->number . ')');
        return "Customer paling banyak overdue:\n- " . $lines->implode("\n- ");
    }

    protected function stockForecast(array &$sources): string
    {
        $rows = StockLedger::join('items', 'items.id', '=', 'stock_ledger.item_id')
            ->selectRaw('items.code, items.name, SUM(qty_in)-SUM(qty_out) bal, SUM(qty_out)/NULLIF(COUNT(DISTINCT trx_date),0) avg_out')
            ->groupBy('items.id', 'items.code', 'items.name')
            ->havingRaw('SUM(qty_in)-SUM(qty_out) > 0')
            ->orderByDesc('bal')->limit(5)->get();
        $sources[] = 'stock_ledger(moving average keluar harian)';
        if ($rows->isEmpty()) {
            return 'Tidak ada stok untuk diprediksi.';
        }
        $lines = $rows->map(function ($r) {
            $days = $r->avg_out > 0 ? round($r->bal / $r->avg_out, 1) : '∞';
            return $r->code . ': saldo ' . number_format($r->bal, 2) . ' ≈ bertahan ' . $days . ' hari pada laju keluar rata-rata.';
        });
        return "Estimasi ketahanan stok (rata-rata keluar harian):\n- " . $lines->implode("\n- ");
    }

    protected function compareSites(array &$sources): string
    {
        $from = now()->subDays(29)->toDateString();
        $rows = MiningActivity::join('sites', 'sites.id', '=', 'mining_activities.site_id')
            ->whereIn('mining_activities.status', ['APPROVED', 'POSTED'])
            ->whereDate('date', '>=', $from)
            ->selectRaw('sites.name, SUM(tonnage) tons, SUM(working_hours) hours')
            ->groupBy('sites.name')->orderByDesc('tons')->get();
        $sources[] = 'mining_activities(30 hari)';
        if ($rows->isEmpty()) {
            return 'Belum ada aktivitas 30 hari terakhir untuk dibandingkan.';
        }
        $lines = $rows->map(fn ($r) => $r->name . ': ' . number_format($r->tons, 2) . ' ton / ' . number_format($r->hours, 1) . ' jam');
        return "Perbandingan site 30 hari terakhir:\n- " . $lines->implode("\n- ");
    }

    protected function productionForecast(array &$sources): string
    {
        $daily = ProductionBatch::where('status', 'POSTED')
            ->whereDate('date', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('DATE(date) d, SUM(net_output) t')->groupBy('d')->orderBy('d')->pluck('t')->all();
        $sources[] = 'production_batches(moving average 7 hari)';
        if (count($daily) < 3) {
            return 'Data produksi belum cukup untuk prediksi (butuh ≥3 hari).';
        }
        $window = array_slice($daily, -7);
        $avg = round(array_sum($window) / count($window), 2);
        return 'Prediksi output crusher 7 hari ke depan ≈ ' . number_format($avg * 7, 2) . ' ton (rata-rata harian ' . number_format($avg, 2) . ' ton dari 7 hari terakhir).';
    }
}
