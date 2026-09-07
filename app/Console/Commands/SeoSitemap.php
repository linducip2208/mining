<?php

namespace App\Console\Commands;

use App\Services\SeoSitemapService;
use Illuminate\Console\Command;

class SeoSitemap extends Command
{
    protected $signature = 'seo:sitemap';

    protected $description = 'Warm the SEO sitemap cache and report indexed pages';

    public function handle(): int
    {
        SeoSitemapService::forget();
        $groups = SeoSitemapService::groups();
        $total = 0;
        foreach ($groups as $g) {
            $this->line(str_pad($g['group'], 24)." => {$g['count']} urls");
            $total += $g['count'];
            SeoSitemapService::child($g['group']);
        }
        $this->info("Sitemap OK: {$total} indexable urls in ".count($groups).' children');

        return self::SUCCESS;
    }
}
