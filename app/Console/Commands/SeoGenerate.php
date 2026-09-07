<?php

namespace App\Console\Commands;

use App\Services\SeoPageGenerator;
use App\Services\SeoSitemapService;
use Illuminate\Console\Command;

class SeoGenerate extends Command
{
    protected $signature = 'seo:generate {--cluster= : only one candidate rule} {--tier=1 : rollout tier 1-5} {--limit= : max candidates to process} {--dry-run : count candidates without writing} {--force : regenerate even unchanged} {--no-publish : keep pages in REVIEW}';

    protected $description = 'Generate programmatic SEO pages with quality gate (idempotent)';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $dry = SeoPageGenerator::dryRun();
            foreach ($dry['rules'] as $rule => $count) {
                $this->line(str_pad($rule, 32)." => {$count}");
            }
            $this->info("TOTAL valid candidates: {$dry['total']} (cap ".SeoPageGenerator::MAX_INDEXABLE_PAGES.')');

            return self::SUCCESS;
        }

        $tier = max(1, min(5, (int) $this->option('tier')));
        $stats = SeoPageGenerator::generate(
            $tier,
            ! $this->option('no-publish'),
            $this->option('cluster'),
            $this->option('limit') ? (int) $this->option('limit') : null
        );
        SeoSitemapService::forget();

        $this->info('created='.$stats['created'].' updated='.$stats['updated'].' published='.$stats['published'].' rejected='.$stats['rejected']);

        return self::SUCCESS;
    }
}
