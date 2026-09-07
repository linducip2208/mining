<?php

namespace App\Services;

use App\Models\SeoFeature;
use App\Models\SeoIndustry;
use App\Models\SeoLocation;
use App\Models\SeoPage;
use App\Models\SeoUseCase;
use Illuminate\Support\Facades\DB;

/**
 * Data-driven candidate enumeration with a hard capacity cap.
 * 22,000 is CAPACITY, not a publish target — tiers gate the rollout.
 */
final class SeoPageGenerator
{
    public const MAX_INDEXABLE_PAGES = 22000;

    public const TIERS = [1 => 300, 2 => 1000, 3 => 5000, 4 => 10000, 5 => 22000];

    public const PILLARS = [
        ['path' => 'erp-mining', 'keyword' => 'erp mining', 'intent' => 'SOFTWARE', 'cluster' => 'core', 'commercial' => 100, 'h1' => 'ERP Mining Terintegrasi untuk Operasi Tambang'],
        ['path' => 'erp-tambang', 'keyword' => 'erp tambang', 'intent' => 'SOFTWARE', 'cluster' => 'core', 'commercial' => 100, 'h1' => 'ERP Tambang Terintegrasi untuk Perusahaan Indonesia'],
        ['path' => 'source-code-erp-mining', 'keyword' => 'source code erp mining', 'intent' => 'SOURCE_CODE', 'cluster' => 'core', 'commercial' => 100, 'h1' => 'Source Code ERP Mining Mulai Rp12 Juta'],
        ['path' => 'source-code-erp-tambang', 'keyword' => 'source code erp tambang', 'intent' => 'SOURCE_CODE', 'cluster' => 'core', 'commercial' => 100, 'h1' => 'Source Code ERP Tambang Mulai Rp12 Juta'],
        ['path' => 'software-pertambangan', 'keyword' => 'software pertambangan', 'intent' => 'SOFTWARE', 'cluster' => 'core', 'commercial' => 95, 'h1' => 'Software Pertambangan Terintegrasi'],
        ['path' => 'aplikasi-pertambangan', 'keyword' => 'aplikasi pertambangan', 'intent' => 'APPLICATION', 'cluster' => 'core', 'commercial' => 90, 'h1' => 'Aplikasi Pertambangan untuk Operasi Modern'],
        ['path' => 'harga-erp-tambang', 'keyword' => 'harga erp tambang', 'intent' => 'PRICE', 'cluster' => 'core', 'commercial' => 100, 'h1' => 'Harga ERP Tambang Mulai Rp12 Juta'],
        ['path' => 'fitur-erp-mining', 'keyword' => 'fitur erp mining', 'intent' => 'FEATURE', 'cluster' => 'core', 'commercial' => 85, 'h1' => 'Fitur ERP Mining Terlengkap'],
    ];

    /**
     * @return array{rules: array<string, int>, total: int, capped: bool}
     */
    public static function dryRun(): array
    {
        $rules = [];
        foreach (self::candidates() as $rule => $list) {
            $rules[$rule] = count($list);
        }
        $total = array_sum($rules);

        return ['rules' => $rules, 'total' => $total, 'capped' => $total > self::MAX_INDEXABLE_PAGES];
    }

    /**
     * @return array<string, array<int, array>>
     */
    public static function candidates(): array
    {
        $features = SeoFeature::marketable()->orderBy('priority')->get();
        $industries = SeoIndustry::orderBy('priority')->get();
        $provinces = SeoLocation::where('type', 'province')->orderBy('priority')->get();
        $cities = SeoLocation::whereIn('type', ['city'])->orderBy('priority')->get();
        $useCases = SeoUseCase::orderBy('priority')->get();

        $out = [
            'pillars' => [],
            'core_commercial' => [],
            'modules' => [],
            'solutions' => [],
            'industries' => [],
            'locations' => [],
            'feature_x_industry' => [],
            'feature_x_province' => [],
            'industry_x_province' => [],
            'industry_x_city' => [],
            'feature_x_industry_x_province' => [],
            'feature_x_city' => [],
        ];

        foreach (self::PILLARS as $p) {
            $out['pillars'][] = array_merge(
                self::base($p['path'], $p['keyword'], $p['intent'], $p['cluster'], $p['commercial']),
                ['h1' => $p['h1']]
            );
        }

        foreach (['jual source code erp mining' => 'jual-source-code-erp-mining', 'beli source code erp mining' => 'beli-source-code-erp-mining', 'harga source code erp mining' => 'harga-source-code-erp-mining', 'jual source code erp tambang' => 'jual-source-code-erp-tambang', 'beli source code erp tambang' => 'beli-source-code-erp-tambang', 'harga source code erp tambang' => 'harga-source-code-erp-tambang', 'software erp pertambangan' => 'software-erp-pertambangan', 'aplikasi tambang' => 'aplikasi-tambang', 'aplikasi mining' => 'aplikasi-mining', 'erp perusahaan tambang' => 'erp-perusahaan-tambang'] as $keyword => $path) {
            $out['core_commercial'][] = self::base($path, $keyword, str_contains($keyword, 'harga') ? 'PRICE' : (str_contains($keyword, 'source code') ? 'SOURCE_CODE' : 'SOFTWARE'), 'core', 95);
        }

        foreach ($features as $f) {
            $out['modules'][] = self::base("modul/{$f->slug}", "{$f->name} tambang", 'FEATURE', 'module', 70, $f->id);
        }
        foreach ($useCases as $u) {
            $out['solutions'][] = self::base("solusi/{$u->slug}", "{$u->name} tambang", 'SOFTWARE', 'usecase', 75, null, null, null, $u->id);
        }
        foreach ($industries as $i) {
            $out['industries'][] = self::base("industri/{$i->slug}", "erp tambang {$i->name}", 'INDUSTRY', 'industry', 80, null, $i->id);
        }
        foreach ($provinces as $p) {
            $out['locations'][] = self::base("lokasi/{$p->slug}/erp-tambang", "erp tambang {$p->name}", 'LOCATION', 'location', 65, null, null, $p->id);
        }
        foreach ($cities as $c) {
            if (! $c->parent) {
                continue;
            }
            $out['locations'][] = self::base("lokasi/{$c->parent->slug}/{$c->slug}", "software pertambangan {$c->name}", 'LOCATION', 'location', 60, null, null, $c->id);
        }

        foreach ($features as $f) {
            foreach ($industries as $i) {
                $out['feature_x_industry'][] = self::base(
                    "industri/{$i->slug}/{$f->slug}", "{$f->name} {$i->name}", 'FEATURE', 'module', 72, $f->id, $i->id
                );
            }
            foreach ($provinces as $p) {
                $out['feature_x_province'][] = self::base(
                    "lokasi/{$p->slug}/{$f->slug}", "{$f->name} {$p->name}", 'LOCATION', 'module', 62, $f->id, null, $p->id
                );
            }
        }
        foreach ($industries as $i) {
            foreach ($provinces as $p) {
                $out['industry_x_province'][] = self::base(
                    "lokasi/{$p->slug}/{$i->slug}", "erp tambang {$i->name} {$p->name}", 'LOCATION', 'industry', 68, null, $i->id, $p->id
                );
            }
            foreach ($cities as $c) {
                if (! $c->parent) {
                    continue;
                }
                $out['industry_x_city'][] = self::base(
                    "lokasi/{$c->parent->slug}/{$c->slug}/{$i->slug}", "erp tambang {$i->name} {$c->name}", 'LOCATION', 'industry', 60, null, $i->id, $c->id
                );
            }
        }

        $topFeatures = $features->take(10);
        foreach ($topFeatures as $f) {
            foreach ($industries as $i) {
                foreach ($provinces as $p) {
                    $out['feature_x_industry_x_province'][] = self::base(
                        "lokasi/{$p->slug}/{$i->slug}/{$f->slug}", "{$f->name} {$i->name} {$p->name}", 'LOCATION', 'module', 55, $f->id, $i->id, $p->id
                    );
                }
            }
        }
        foreach ($features->take(20) as $f) {
            foreach ($cities as $c) {
                if (! $c->parent) {
                    continue;
                }
                $out['feature_x_city'][] = self::base(
                    "lokasi/{$c->parent->slug}/{$c->slug}/{$f->slug}", "{$f->name} {$c->name}", 'LOCATION', 'module', 50, $f->id, null, $c->id
                );
            }
        }

        return $out;
    }

    protected static function base(
        string $path,
        string $keyword,
        string $intent,
        string $cluster,
        int $commercial,
        ?int $featureId = null,
        ?int $industryId = null,
        ?int $locationId = null,
        ?int $useCaseId = null
    ): array {
        return compact('path', 'keyword', 'intent', 'cluster', 'commercial', 'featureId', 'industryId', 'locationId', 'useCaseId');
    }

    /**
     * Generate (idempotent upsert) + quality gate. Only PASS pages within the
     * tier cap become PUBLISHED/indexable.
     *
     * @return array{created:int, updated:int, published:int, rejected:int, capped:bool}
     */
    public static function generate(int $tier = 1, bool $publish = true, ?string $onlyRule = null, ?int $limit = null): array
    {
        $cap = self::TIERS[$tier] ?? self::TIERS[1];
        $stats = ['created' => 0, 'updated' => 0, 'published' => 0, 'rejected' => 0, 'capped' => false];
        $processed = 0;
        $publishedTotal = SeoPage::where('status', 'PUBLISHED')->count();

        foreach (self::candidates() as $rule => $list) {
            if ($onlyRule && $rule !== $onlyRule) {
                continue;
            }
            foreach ($list as $c) {
                if ($limit && $processed >= $limit) {
                    break 2;
                }
                if (! self::validCandidate($c)) {
                    $stats['rejected']++;

                    continue;
                }
                $processed++;
                $fingerprint = hash('sha256', implode('|', [
                    $c['intent'], strtolower($c['keyword']),
                    $c['industryId'] ?? '-', $c['locationId'] ?? '-',
                    $c['featureId'] ?? '-', $c['useCaseId'] ?? '-',
                ]));

                $publishedCount = SeoPage::where('status', 'PUBLISHED')->count();
                $page = SeoPage::firstOrNew(['fingerprint' => $fingerprint]);
                $isNew = ! $page->exists;

                $result = DB::transaction(function () use ($page, $c, $fingerprint, $publish, &$publishedTotal, $cap) {
                    $feature = $c['featureId'] ? SeoFeature::find($c['featureId']) : null;
                    $industry = $c['industryId'] ? SeoIndustry::find($c['industryId']) : null;
                    $location = $c['locationId'] ? SeoLocation::find($c['locationId']) : null;
                    $useCase = $c['useCaseId'] ? SeoUseCase::find($c['useCaseId']) : null;
                    if (($c['featureId'] && ! $feature) || ($c['industryId'] && ! $industry) || ($c['locationId'] && ! $location)) {
                        return 'rejected';
                    }

                    $content = SeoContentService::compose($c['keyword'], $c['intent'], $feature, $industry, $location, $useCase);
                    $hash = hash('sha256', json_encode($content));

                    $page->fill([
                        'fingerprint' => $fingerprint,
                        'path' => $c['path'],
                        'intent' => $c['intent'],
                        'cluster' => $c['cluster'],
                        'keyword' => $c['keyword'],
                        'title' => self::title($c, $feature, $industry, $location),
                        'h1' => $c['h1'] ?? self::h1($c, $feature, $industry, $location),
                        'description' => self::description($c, $feature, $industry, $location),
                        'canonical' => url('/'.$c['path']),
                        'industry_id' => $industry?->id,
                        'location_id' => $location?->id,
                        'feature_id' => $feature?->id,
                        'use_case_id' => $useCase?->id,
                        'commercial_score' => $c['commercial'],
                        'content' => $content,
                    ]);

                    $changed = $page->content_hash !== $hash;
                    if ($changed) {
                        $page->content_hash = $hash;
                        $page->content_version = ($page->content_version ?: 0) + 1;
                    }
                    if (! $page->exists) {
                        $page->status = 'DRAFT';
                        $page->generated_at = now();
                    }
                    $page->save();

                    $audit = SeoQualityService::audit($page, true);

                    if ($audit['verdict'] === 'FAIL') {
                        return 'rejected';
                    }
                    if ($publish && $page->status !== 'PUBLISHED' && $publishedTotal < $cap && $publishedTotal < self::MAX_INDEXABLE_PAGES) {
                        $page->status = 'PUBLISHED';
                        $page->indexable = true;
                        $page->published_at = now();
                        $page->save();
                        $publishedTotal++;

                        return 'published';
                    }
                    if ($page->status !== 'PUBLISHED' && $page->status !== 'NOINDEX') {
                        $page->status = 'REVIEW';
                        $page->save();
                    }

                    return 'review';
                });

                if ($result === 'published' && $publishedTotal >= $cap) {
                    $stats['published']++;
                    break 2;
                }

                match ($result) {
                    'published' => $stats['published']++,
                    'rejected' => $stats['rejected']++,
                    default => $stats[$isNew ? 'created' : 'updated']++,
                };
            }
        }

        $stats['capped'] = SeoPage::where('status', 'PUBLISHED')->count() >= self::MAX_INDEXABLE_PAGES;

        return $stats;
    }

    protected static function validCandidate(array $c): bool
    {
        $kw = strtolower($c['keyword']);
        // reject unnatural / intent-less combos
        if (strlen($kw) < 4 || str_word_count($kw) > 8) {
            return false;
        }
        if (preg_match('/\b(test|xxx|asdf)\b/', $kw)) {
            return false;
        }

        return true;
    }

    protected static function title(array $c, $feature, $industry, $location): string
    {
        $kw = ucwords($c['keyword']);
        $suffix = match ($c['intent']) {
            'PRICE', 'SOURCE_CODE', 'BUY' => 'Mulai Rp12 Juta',
            default => 'Source Code Mulai Rp12 Juta',
        };

        return "{$kw} | {$suffix} | Mining ERP";
    }

    protected static function h1(array $c, $feature, $industry, $location): string
    {
        $kw = ucwords($c['keyword']);
        if ($location) {
            return "{$kw} untuk Perusahaan di {$location->name}";
        }
        if ($industry) {
            return "{$kw} untuk {$industry->name}";
        }

        return $kw;
    }

    protected static function description(array $c, $feature, $industry, $location): string
    {
        $kw = ucwords($c['keyword']);
        $scope = $location ? " untuk perusahaan di {$location->name}" : ($industry ? " untuk {$industry->name}" : '');
        $price = 'Source code mulai Rp12 juta; harga akhir mengikuti scope dan customization.';

        return "{$kw}{$scope}: kelola operasional tambang terintegrasi — produksi, timbangan, stok, fleet, BBM, penjualan hingga accounting. {$price}";
    }
}
