<?php

namespace App\Services;

use App\Models\AiAuditLog;
use App\Services\Ai\HttpLlmProvider;
use App\Services\Ai\LocalAnalystProvider;
use Illuminate\Support\Facades\DB;

/**
 * AI Copilot — READ-ONLY by design.
 * Only whitelisted read queries run; every Q&A is audit-logged.
 * Sensitive fields (password, tokens, keys) are never selected.
 */
class AiCopilotService
{
    public static function providers(): array
    {
        return HttpLlmProvider::supported();
    }

    public static function configuredProvider(): string
    {
        $p = strtoupper((string) \App\Models\Setting::get('ai.provider', 'LOCAL'));
        return in_array($p, self::providers()) ? $p : 'LOCAL';
    }

    public static function ask(int $userId, string $question): array
    {
        $question = trim(mb_substr($question, 0, 1000));
        if ($question === '') {
            throw new \InvalidArgumentException('Pertanyaan kosong.');
        }
        $provider = self::configuredProvider();
        $sources = [];

        if ($provider === 'LOCAL') {
            $result = (new LocalAnalystProvider())->ask($question, '');
            // capture sources via reflection-free re-run hook
            $sources = self::extractSources($question);
        } else {
            $context = self::buildContext($question);
            $result = (new HttpLlmProvider($provider))->ask($question, $context);
            $sources = ['controlled-queries'];
        }

        AiAuditLog::create([
            'user_id' => $userId,
            'provider' => $provider,
            'question' => $question,
            'answer' => mb_substr($result['answer'] ?? '', 0, 65000),
            'data_sources' => $sources,
            'ip_address' => request()?->ip(),
        ]);

        return [
            'answer' => $result['answer'] ?? '',
            'provider' => $provider,
            'model' => $result['model'] ?? '-',
            'sources' => $sources,
        ];
    }

    protected static function extractSources(string $question): array
    {
        // mirrors LocalAnalystProvider intents (read-only table names only)
        $q = mb_strtolower($question);
        return match (true) {
            str_contains($q, 'downtime') => ['work_orders'],
            str_contains($q, 'solar') || str_contains($q, 'bbm') || str_contains($q, 'fuel') => ['fuel_issues'],
            str_contains($q, 'overdue') || str_contains($q, 'piutang') => ['invoices'],
            str_contains($q, 'stok') => ['stock_ledger'],
            str_contains($q, 'site') => ['mining_activities'],
            str_contains($q, 'produksi') => ['production_batches'],
            default => ['general'],
        };
    }

    /**
     * Compact read-only snapshot for LLM context. Aggregates only —
     * never row-level sensitive data, never credentials.
     */
    protected static function buildContext(string $question): string
    {
        $lines = [];
        $lines[] = 'Produksi 7 hari (ton): ' . \App\Models\ProductionBatch::where('status', 'POSTED')->whereDate('date', '>=', now()->subDays(6)->toDateString())->sum('net_output');
        $lines[] = 'Stok kritis: ' . \App\Models\Item::where('min_stock', '>', 0)->whereRaw('(SELECT COALESCE(SUM(qty_in-qty_out),0) FROM stock_ledger WHERE stock_ledger.item_id = items.id) < min_stock')->count() . ' item';
        $lines[] = 'Piutang outstanding: Rp ' . number_format(\App\Models\Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->selectRaw('COALESCE(SUM(total-paid_amount),0) b')->value('b'), 0);
        $lines[] = 'Faktur overdue: ' . \App\Models\Invoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID'])->whereDate('due_date', '<', now())->count();
        $lines[] = 'WO terbuka: ' . \App\Models\WorkOrder::whereIn('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'IN_PROGRESS'])->count();
        $lines[] = 'Downtime bulan ini (jam): ' . \App\Models\WorkOrder::whereMonth('date', now()->month)->sum('downtime_hours');
        $lines[] = 'Approval pending: ' . \App\Models\ApprovalRequest::where('status', 'PENDING')->count();
        $lines[] = 'BBM 7 hari (liter): ' . \App\Models\FuelIssue::where('status', 'POSTED')->whereDate('issue_date', '>=', now()->subDays(6)->toDateString())->sum('liter');
        return implode("\n", $lines);
    }

    public static function history(int $userId, int $limit = 20)
    {
        return AiAuditLog::where('user_id', $userId)->latest('id')->limit($limit)->get();
    }
}
