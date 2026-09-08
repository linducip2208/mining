<?php

namespace App\Console\Commands;

use App\Models\SeoPage;
use App\Services\SeoQualityService;
use Illuminate\Console\Command;

class SeoAudit extends Command
{
    protected $signature = 'seo:audit {--limit=500 : max pages to re-check}';

    protected $description = 'Re-run the SEO quality gate over generated pages';

    public function handle(): int
    {
        $pass = $warn = $fail = 0;
        $pages = SeoPage::orderBy('id')->limit((int) $this->option('limit'))->get();
        foreach ($pages as $page) {
            $result = SeoQualityService::audit($page);
            match ($result['verdict']) {
                'PASS' => $pass++,
                'WARNING' => $warn++,
                default => $fail++,
            };
            if ($result['verdict'] === 'FAIL') {
                $this->line("FAIL #{$page->id} {$page->path}: ".implode('; ', array_slice($result['reasons'], 0, 2)));
            }
        }

        $overview = SeoQualityService::overview();
        $this->info("PASS={$pass} WARNING={$warn} FAIL={$fail} | indexable={$overview['indexable']} orphans={$overview['orphans']} dup_titles={$overview['duplicate_titles']} thin={$overview['thin_pages']}");

        $broken = SeoQualityService::brokenInternalLinks();
        if ($broken !== []) {
            foreach (array_slice($broken, 0, 10) as $b) {
                $this->warn("BROKEN LINK {$b['path']} → {$b['link']}: {$b['reason']}");
            }
        }
        $this->info('Broken internal links: '.count($broken));

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
