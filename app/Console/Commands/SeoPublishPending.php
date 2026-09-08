<?php

namespace App\Console\Commands;

use App\Models\SeoPage;
use App\Services\SeoPageGenerator;
use App\Services\SeoQualityService;
use Illuminate\Console\Command;

/**
 * Rollout continuation: pages already created + quality-gated (REVIEW =
 * eligible but blocked by the tier cap at generation time) are audited again
 * and published up to the requested tier cap. Never bypasses the gate.
 */
class SeoPublishPending extends Command
{
    protected $signature = 'seo:publish-pending {--tier=2 : rollout tier 1-5 cap} {--limit= : max pages to publish}';

    protected $description = 'Publikasikan halaman REVIEW yang lolos quality gate sampai batas tier rollout';

    public function handle(): int
    {
        $cap = SeoPageGenerator::TIERS[max(1, min(5, (int) $this->option('tier')))] ?? SeoPageGenerator::TIERS[1];
        $limit = $this->option('limit') ? (int) $this->option('limit') : PHP_INT_MAX;
        $publishedTotal = SeoPage::where('status', 'PUBLISHED')->count();
        $published = 0;

        $pending = SeoPage::where('status', 'REVIEW')->orderByDesc('commercial_score')->orderBy('id')->get();
        foreach ($pending as $page) {
            if ($published >= $limit || $publishedTotal >= $cap || $publishedTotal >= SeoPageGenerator::MAX_INDEXABLE_PAGES) {
                break;
            }
            $audit = SeoQualityService::audit($page, false);
            if ($audit['verdict'] === 'FAIL') {
                continue;
            }
            $page->status = 'PUBLISHED';
            $page->indexable = true;
            $page->published_at = $page->published_at ?? now();
            $page->save();
            $publishedTotal++;
            $published++;
        }

        $this->info("Dipublikasikan: {$published} · total indexable sekarang: ".SeoPage::where('status', 'PUBLISHED')->count()." (tier cap {$cap})");

        return self::SUCCESS;
    }
}
