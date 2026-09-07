<?php

namespace App\Services;

use App\Models\SeoPage;
use Illuminate\Support\Facades\Cache;

/**
 * Contextual related pages (max 12). Pillars act as hubs so every
 * indexable page has at least one inbound link — orphan target is zero.
 */
final class SeoInternalLinkService
{
    public const MAX_LINKS = 12;

    /**
     * @return array<int, array{title:string, url:string, label:string}>
     */
    public static function related(SeoPage $page): array
    {
        return Cache::remember("seo_related.{$page->id}.v{$page->content_version}", 3600, function () use ($page) {
            $ids = [$page->id];
            $out = [];

            $push = function ($query, string $label) use (&$out, &$ids) {
                foreach ($query->get() as $related) {
                    if (in_array($related->id, $ids, true)) {
                        continue;
                    }
                    $ids[] = $related->id;
                    $out[] = ['title' => $related->title, 'url' => $related->url(), 'label' => $label];
                    if (count($out) >= self::MAX_LINKS) {
                        break;
                    }
                }
            };

            if ($page->feature_id) {
                $push(SeoPage::indexable()->where('feature_id', $page->feature_id)->orderBy('commercial_score', 'desc')->limit(4), 'Fitur terkait');
            }
            if ($page->industry_id && count($out) < self::MAX_LINKS) {
                $push(SeoPage::indexable()->where('industry_id', $page->industry_id)->orderBy('commercial_score', 'desc')->limit(4), 'Industri terkait');
            }
            if ($page->location_id && count($out) < self::MAX_LINKS) {
                $push(SeoPage::indexable()->where('location_id', $page->location_id)->orderBy('commercial_score', 'desc')->limit(3), 'Lokasi terkait');
            }
            if (count($out) < self::MAX_LINKS) {
                $push(SeoPage::indexable()->where('cluster', $page->cluster)->orderBy('commercial_score', 'desc')->limit(4), 'Topik terkait');
            }
            if (count($out) < self::MAX_LINKS) {
                $push(SeoPage::indexable()->where('cluster', 'core')->orderBy('commercial_score', 'desc')->limit(4), 'Halaman utama');
            }

            return array_values(array_slice($out, 0, self::MAX_LINKS));
        });
    }

    /**
     * Hub links rendered on pillar pages — guarantees inbound links for children.
     *
     * @return array<int, array{title:string, url:string, label:string}>
     */
    public static function hubLinks(SeoPage $pillar, int $limit = 24): array
    {
        $children = SeoPage::indexable()
            ->where('id', '!=', $pillar->id)
            ->where(function ($q) use ($pillar) {
                $q->where('cluster', $pillar->cluster);
                if ($pillar->feature_id) {
                    $q->orWhere('feature_id', $pillar->feature_id);
                }
                if ($pillar->industry_id) {
                    $q->orWhere('industry_id', $pillar->industry_id);
                }
            })
            ->orderBy('commercial_score', 'desc')
            ->limit($limit)
            ->get();

        return $children->map(fn (SeoPage $p) => [
            'title' => $p->title, 'url' => $p->url(), 'label' => $p->keyword,
        ])->all();
    }

    public static function breadcrumbs(SeoPage $page): array
    {
        $crumbs = [['name' => 'Beranda', 'url' => url('/')]];
        $segment = explode('/', $page->path)[0];
        $labels = [
            'modul' => 'Fitur ERP Mining', 'industri' => 'Industri', 'lokasi' => 'Lokasi',
            'solusi' => 'Solusi', 'harga' => 'Harga', 'fitur' => 'Fitur',
        ];
        if (isset($labels[$segment]) && $page->cluster !== 'core') {
            $crumbs[] = ['name' => $labels[$segment], 'url' => url('/'.$segment)];
        }
        if ($page->location_id && $page->location?->parent) {
            $crumbs[] = ['name' => 'ERP Tambang '.$page->location->parent->name, 'url' => url('/lokasi/'.$page->location->parent->slug.'/erp-tambang')];
        }
        $crumbs[] = ['name' => $page->h1, 'url' => $page->url()];

        return $crumbs;
    }
}
