<?php

namespace App\Http\Controllers;

use App\Models\SeoFeature;
use App\Models\SeoIndustry;
use App\Models\SeoKeyword;
use App\Models\SeoLocation;
use App\Models\SeoPage;
use App\Models\SeoUseCase;
use App\Services\AuditService;
use App\Services\SeoInternalLinkService;
use App\Services\SeoPageGenerator;
use App\Services\SeoQualityService;
use App\Services\SeoSitemapService;
use Illuminate\Http\Request;

class MarketingSeoController extends Controller
{
    public function dashboard()
    {
        return view('marketing.seo.dashboard', [
            'overview' => SeoQualityService::overview(),
            'capacity' => SeoPageGenerator::MAX_INDEXABLE_PAGES,
            'tiers' => SeoPageGenerator::TIERS,
            'rules' => SeoPageGenerator::dryRun(),
            'sitemaps' => SeoSitemapService::groups(),
        ]);
    }

    public function pages(Request $request)
    {
        $items = SeoPage::with(['feature', 'industry', 'location'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->cluster, fn ($q) => $q->where('cluster', $request->cluster))
            ->when($request->q, fn ($q) => $q->where(fn ($w) => $w->where('keyword', 'like', "%{$request->q}%")->orWhere('path', 'like', "%{$request->q}%")))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('marketing.seo.pages', [
            'items' => $items,
            'statuses' => SeoPage::STATUSES,
            'clusters' => SeoPage::distinct()->pluck('cluster'),
        ]);
    }

    public function show(SeoPage $seo_page)
    {
        $seo_page->load(['feature', 'industry', 'location', 'useCase']);

        return view('marketing.seo.show', [
            'page' => $seo_page,
            'related' => SeoInternalLinkService::related($seo_page),
        ]);
    }

    public function publish(SeoPage $seo_page)
    {
        $result = SeoQualityService::audit($seo_page);
        if ($result['verdict'] === 'FAIL') {
            return back()->with('error', 'Gagal quality gate: '.implode('; ', array_slice($result['reasons'], 0, 2)));
        }
        $seo_page->status = 'PUBLISHED';
        $seo_page->indexable = true;
        $seo_page->published_at = now();
        $seo_page->reviewed_at = now();
        $seo_page->save();
        AuditService::log('UPDATE', 'MARKETING', $seo_page->id, SeoPage::class, null, ['status' => 'PUBLISHED']);
        SeoSitemapService::forget();

        return back()->with('success', 'Halaman dipublish.');
    }

    public function noindex(SeoPage $seo_page, Request $request)
    {
        $validated = $request->validate(['reason' => 'nullable|max:200']);
        $seo_page->status = 'NOINDEX';
        $seo_page->indexable = false;
        $seo_page->noindex_reason = $validated['reason'] ?? 'manual review';
        $seo_page->save();
        AuditService::log('UPDATE', 'MARKETING', $seo_page->id, SeoPage::class, null, ['status' => 'NOINDEX']);
        SeoSitemapService::forget();

        return back()->with('success', 'Halaman di-noindex.');
    }

    public function archive(SeoPage $seo_page, Request $request)
    {
        $validated = $request->validate(['redirect_to' => 'nullable|max:255']);
        $seo_page->status = 'ARCHIVED';
        $seo_page->indexable = false;
        $seo_page->redirect_to = $validated['redirect_to'] ?? null;
        $seo_page->save();
        AuditService::log('UPDATE', 'MARKETING', $seo_page->id, SeoPage::class, null, ['status' => 'ARCHIVED']);
        SeoSitemapService::forget();

        return back()->with('success', 'Halaman diarsipkan.');
    }

    public function catalog(Request $request)
    {
        $tab = $request->get('tab', 'features');

        return view('marketing.seo.catalog', [
            'tab' => $tab,
            'features' => $tab === 'features' ? SeoFeature::orderBy('priority')->paginate(20) : null,
            'industries' => $tab === 'industries' ? SeoIndustry::orderBy('priority')->paginate(20) : null,
            'locations' => $tab === 'locations' ? SeoLocation::orderBy('priority')->paginate(20) : null,
            'usecases' => $tab === 'usecases' ? SeoUseCase::orderBy('priority')->paginate(20) : null,
            'keywords' => $tab === 'keywords' ? SeoKeyword::orderByDesc('commercial_score')->paginate(20) : null,
        ]);
    }

    public function toggleFeature(SeoFeature $feature)
    {
        $feature->marketing_enabled = ! $feature->marketing_enabled;
        $feature->save();
        AuditService::log('UPDATE', 'MARKETING', $feature->id, SeoFeature::class, null, ['marketing_enabled' => $feature->marketing_enabled]);

        return back()->with('success', 'Status marketing fitur diperbarui.');
    }
}
