<?php

namespace App\Services;

use App\Models\SeoPage;
use Illuminate\Support\Facades\Cache;

/**
 * Sitemap index + children. Only indexable=true pages, honest lastmod
 * (updated_at changes only when content regenerates).
 */
final class SeoSitemapService
{
    public const PER_CHILD = 2000;

    /**
     * @return array<int, array{group:string, url:string, count:int}>
     */
    public static function groups(): array
    {
        return Cache::remember('seo_sitemap_groups', 3600, function () {
            $groups = [];
            $clusters = SeoPage::indexable()
                ->selectRaw('cluster, COUNT(*) c')
                ->groupBy('cluster')->pluck('c', 'cluster')->all();
            foreach ($clusters as $cluster => $count) {
                $parts = (int) ceil($count / self::PER_CHILD);
                for ($i = 1; $i <= $parts; $i++) {
                    $name = $parts > 1 ? "{$cluster}-{$i}" : $cluster;
                    $groups[] = [
                        'group' => $name,
                        'url' => url("/sitemaps/{$name}.xml"),
                        'count' => $i < $parts ? self::PER_CHILD : $count - ($parts - 1) * self::PER_CHILD,
                    ];
                }
            }

            return $groups;
        });
    }

    public static function child(string $group): ?string
    {
        if (! preg_match('/^([a-z0-9_]+?)(?:-(\d+))?$/', $group, $m)) {
            return null;
        }
        $cluster = $m[1];
        $page = max(1, (int) ($m[2] ?? 1));

        $known = SeoPage::indexable()->distinct()->pluck('cluster')->all();
        if (! in_array($cluster, $known, true)) {
            return null;
        }

        $urls = Cache::remember("seo_sitemap_child.{$cluster}.{$page}", 3600, function () use ($cluster, $page) {
            return SeoPage::indexable()->where('cluster', $cluster)
                ->orderBy('id')
                ->forPage($page, self::PER_CHILD)
                ->get(['path', 'updated_at'])
                ->map(fn ($p) => ['loc' => url('/'.$p->path), 'lastmod' => $p->updated_at->toAtomString()])
                ->all();
        });

        if ($urls === []) {
            return null;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url><loc>{$u['loc']}</loc><lastmod>{$u['lastmod']}</lastmod></url>\n";
        }

        return $xml.'</urlset>';
    }

    public static function index(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (self::groups() as $g) {
            $xml .= "  <sitemap><loc>{$g['url']}</loc></sitemap>\n";
        }

        return $xml.'</sitemapindex>';
    }

    public static function forget(): void
    {
        Cache::forget('seo_sitemap_groups');
        foreach (self::groups() as $g) {
            Cache::forget('seo_sitemap_child.'.str_replace('.xml', '', basename($g['url'])));
        }
    }
}
