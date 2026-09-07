<?php

namespace App\Services;

use App\Models\SeoFeature;
use App\Models\SeoPage;
use Illuminate\Support\Facades\DB;

/**
 * Quality gate: PASS / WARNING / FAIL + indexable decision.
 * A page is indexed only when every hard gate passes.
 */
final class SeoQualityService
{
    public const QUALITY_THRESHOLD = 60;

    public const UNIQUENESS_THRESHOLD = 60;

    public const BANNED_CLAIMS = [
        'terbaik indonesia', 'nomor 1', 'no. 1', 'no 1',
        'dipakai 500 perusahaan', 'terpercaya sejak 10 tahun',
        'hemat biaya 70%', 'meningkatkan produksi 300%',
    ];

    /**
     * @return array{verdict:string, quality:int, uniqueness:int, reasons:string[]}
     */
    public static function check(SeoPage $page, bool $forGeneration = false): array
    {
        $reasons = [];
        $warnings = [];
        $content = $page->content ?? [];

        $quality = 100;
        $uniqueness = 100;

        // --- hard gates: metadata uniqueness ---
        foreach (['title' => 'judul', 'h1' => 'H1', 'description' => 'deskripsi'] as $field => $label) {
            if (blank($page->{$field})) {
                $reasons[] = "{$label} kosong";

                continue;
            }
            $dupes = SeoPage::where($field, $page->{$field})->where('id', '!=', $page->id)->count();
            if ($dupes > 0) {
                $reasons[] = "{$label} duplikat ({$dupes} halaman)";
                $uniqueness -= 25;
            }
        }
        if (blank($page->canonical) || ! str_starts_with((string) $page->canonical, url('/'))) {
            $reasons[] = 'canonical tidak valid';
        }

        // --- hard gates: content completeness ---
        $faqCount = count($content['faqs'] ?? []);
        $featureCount = count($content['features'] ?? []);
        $workflowCount = count($content['workflow'] ?? []);
        if ($faqCount < 5) {
            $reasons[] = "FAQ kurang ({$faqCount}, minimal 5)";
            $quality -= 15;
        }
        if ($featureCount < 3) {
            $reasons[] = "fitur kurang ({$featureCount}, minimal 3)";
            $quality -= 15;
        }
        if ($workflowCount < 3) {
            $reasons[] = "workflow kurang ({$workflowCount}, minimal 3)";
            $quality -= 10;
        } elseif ($workflowCount < 4) {
            $warnings[] = "workflow tipis ({$workflowCount}, ideal 4+)";
            $quality -= 5;
        }
        if (blank($content['price']['display'] ?? null)) {
            $reasons[] = 'harga tidak tampil';
            $quality -= 10;
        }

        // --- hard gates: banned claims ---
        $haystack = strtolower($page->title.' '.$page->h1.' '.$page->description.' '.json_encode($content));
        foreach (self::BANNED_CLAIMS as $claim) {
            if (str_contains($haystack, $claim)) {
                $reasons[] = "klaim dilarang: \"{$claim}\"";
                $quality -= 20;
            }
        }

        // --- hard gates: feature claims must be implemented + enabled ---
        foreach ($content['features'] ?? [] as $f) {
            $feature = SeoFeature::where('slug', $f['slug'] ?? '')->first();
            if (! $feature || ! $feature->implemented || ! $feature->marketing_enabled) {
                $reasons[] = 'klaim fitur belum implemented/enabled: '.($f['slug'] ?? '?');
                $quality -= 15;
            }
        }

        // --- hard gates: location wording (no fake office claims) ---
        foreach (['kantor kami di', 'tim kami di', 'cabang kami di'] as $bad) {
            if (str_contains($haystack, $bad)) {
                $reasons[] = "klaim lokasi fisik dilarang: \"{$bad}\"";
                $quality -= 20;
            }
        }

        // --- warnings (non-blocking) ---
        // During generation siblings may not exist yet — skip the related
        // signal there; the audit command and overview still catch orphans.
        if (! $forGeneration && SeoInternalLinkService::related($page) === []) {
            $warnings[] = 'tanpa related links (calon orphan)';
            $quality -= 5;
            $uniqueness -= 5;
        }
        if (str_word_count($page->title) > 14) {
            $warnings[] = 'judul berpotensi stuffing';
            $quality -= 5;
        }

        $quality = max(0, min(100, $quality));
        $uniqueness = max(0, min(100, $uniqueness));

        if ($reasons !== [] || $quality < self::QUALITY_THRESHOLD || $uniqueness < self::UNIQUENESS_THRESHOLD) {
            $verdict = 'FAIL';
        } elseif ($warnings !== []) {
            $verdict = 'WARNING';
        } else {
            $verdict = 'PASS';
        }

        return ['verdict' => $verdict, 'quality' => $quality, 'uniqueness' => $uniqueness, 'reasons' => array_merge($reasons, $warnings)];
    }

    /**
     * Apply check result to the page (FAIL demotes to NOINDEX; PASS/WARNING
     * keep the page eligible — publishing is the generator's decision).
     *
     * @return array{verdict:string, quality:int, uniqueness:int, reasons:string[]}
     */
    public static function audit(SeoPage $page, bool $forGeneration = false): array
    {
        $result = self::check($page, $forGeneration);
        $page->quality_score = $result['quality'];
        $page->uniqueness_score = $result['uniqueness'];
        $page->last_quality_check_at = now();
        if ($result['verdict'] === 'FAIL') {
            $page->indexable = false;
            $page->noindex_reason = implode('; ', array_slice($result['reasons'], 0, 3));
            if ($page->status === 'PUBLISHED') {
                $page->status = 'NOINDEX';
            }
        } else {
            $page->noindex_reason = null;
            if ($page->status === 'PUBLISHED') {
                $page->indexable = true;
            }
        }
        $page->save();

        return $result;
    }

    /**
     * Global audit counters for dashboards and the content-audit doc.
     */
    public static function overview(): array
    {
        $dup = fn (string $field) => DB::table('seo_pages')
            ->select($field, DB::raw('COUNT(*) c'))->groupBy($field)->having('c', '>', 1)->count();

        return [
            'generated' => SeoPage::count(),
            'indexable' => SeoPage::indexable()->count(),
            'noindex' => SeoPage::where('indexable', false)->count(),
            'by_status' => SeoPage::select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status')->all(),
            'duplicate_titles' => $dup('title'),
            'duplicate_descriptions' => $dup('description'),
            'duplicate_h1' => $dup('h1'),
            'thin_pages' => SeoPage::where('quality_score', '<', self::QUALITY_THRESHOLD)->count(),
            'orphans' => self::orphanCount(),
            'avg_quality' => round((float) SeoPage::avg('quality_score'), 1),
        ];
    }

    public static function orphanCount(): int
    {
        // inbound = shares cluster/feature/industry/location with another
        // indexable page (pillars hub-link their cluster, related() covers
        // the rest). NOT EXISTS avoids NOT IN null/correlation pitfalls.
        return SeoPage::indexable()->whereNotExists(function ($q) {
            $q->selectRaw('1')->from('seo_pages as p')
                ->where('p.status', 'PUBLISHED')->where('p.indexable', true)
                ->whereColumn('p.id', '!=', 'seo_pages.id')
                ->where(function ($w) {
                    $w->whereColumn('p.cluster', 'seo_pages.cluster')
                        ->orWhereColumn('p.feature_id', 'seo_pages.feature_id')
                        ->orWhereColumn('p.industry_id', 'seo_pages.industry_id')
                        ->orWhereColumn('p.location_id', 'seo_pages.location_id');
                });
        })->count();
    }
}
