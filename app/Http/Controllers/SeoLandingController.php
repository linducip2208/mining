<?php

namespace App\Http\Controllers;

use App\Models\SeoCtaClick;
use App\Models\SeoFeature;
use App\Models\SeoIndustry;
use App\Models\SeoLocation;
use App\Models\SeoPage;
use App\Services\SeoInternalLinkService;
use App\Services\SeoSchemaService;
use App\Services\SeoSitemapService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class SeoLandingController extends Controller
{
    public function show(string $path)
    {
        $page = SeoPage::where('path', $path)->first();

        if (! $page) {
            abort(404);
        }
        if ($page->status === 'ARCHIVED') {
            if ($page->redirect_to) {
                return redirect($page->redirect_to, 301);
            }
            abort(410);
        }
        if (! in_array($page->status, ['PUBLISHED', 'NOINDEX'], true)) {
            abort(404);
        }

        $content = $page->content ?? [];
        $breadcrumbs = SeoInternalLinkService::breadcrumbs($page);
        $related = SeoInternalLinkService::related($page);
        $hub = $page->cluster === 'core' ? SeoInternalLinkService::hubLinks($page) : [];
        $schema = SeoSchemaService::forPage($page, $content, $breadcrumbs);
        $wa = WhatsappService::defaultMessage($page->title, $page->intent);
        $meta = [
            'title' => $page->title,
            'description' => $page->description,
            'canonical' => $page->canonical ?: $page->url(),
            'robots' => $page->isNoindex() ? 'noindex,follow' : 'index,follow',
        ];

        return response()
            ->view('seo.landing', compact('page', 'content', 'breadcrumbs', 'related', 'hub', 'schema', 'wa', 'meta'))
            ->header('X-Robots-Tag', $page->isNoindex() ? 'noindex, follow' : 'all');
    }

    public function finder(Request $request)
    {
        $features = SeoFeature::marketable()->orderBy('priority')->get();
        $industries = SeoIndustry::orderBy('priority')->get();
        $locations = SeoLocation::where('type', 'province')->orderBy('priority')->get();
        $results = collect();
        if ($request->filled('feature') || $request->filled('industry') || $request->filled('location')) {
            $results = SeoPage::indexable()
                ->when($request->feature, fn ($q) => $q->whereHas('feature', fn ($w) => $w->where('slug', $request->feature)))
                ->when($request->industry, fn ($q) => $q->whereHas('industry', fn ($w) => $w->where('slug', $request->industry)))
                ->when($request->location, fn ($q) => $q->whereHas('location', fn ($w) => $w->where('slug', $request->location)))
                ->orderBy('commercial_score', 'desc')->limit(24)->get();
        }

        $meta = [
            'title' => 'Cari Solusi ERP Tambang | Mining ERP',
            'description' => 'Temukan solusi ERP pertambangan berdasarkan industri, modul, dan lokasi. Source code mulai Rp12 juta.',
            'canonical' => url('/cari-solusi'),
            'robots' => 'noindex,follow',
        ];
        $schema = ['@context' => 'https://schema.org', '@graph' => []];

        return response()
            ->view('seo.finder', compact('features', 'industries', 'locations', 'results', 'meta', 'schema'))
            ->header('X-Robots-Tag', 'noindex, follow');
    }

    public function click(Request $request)
    {
        $validated = $request->validate([
            'seo_page_id' => 'required|exists:seo_pages,id',
            'cta_position' => 'required|string|max:50',
            'cta_type' => 'required|string|max:50',
        ]);
        SeoCtaClick::create($validated);

        return response()->json(['ok' => true]);
    }

    public function sitemapIndex()
    {
        return response(SeoSitemapService::index(), 200, ['Content-Type' => 'application/xml']);
    }

    public function sitemapChild(string $group)
    {
        $xml = SeoSitemapService::child($group);
        if (! $xml) {
            abort(404);
        }

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        $lines = ['User-agent: *', 'Allow: /'];
        foreach (['/admin', '/login', '/docs', '/cari-solusi', '/seo/preview'] as $blocked) {
            $lines[] = "Disallow: {$blocked}";
        }
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
