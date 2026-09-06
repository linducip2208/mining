<?php

namespace App\Http\Controllers;

use App\Docs\DocRegistry;
use Illuminate\Http\Request;

class DocsController extends Controller
{
    protected function guard()
    {
        if (!DocRegistry::isPublic() && !auth()->check()) {
            return redirect()->route('login');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        return view('docs.index', ['sections' => DocRegistry::sections()]);
    }

    public function section(string $section)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $sections = DocRegistry::sections();
        abort_unless(isset($sections[$section]), 404);
        $first = array_key_first($sections[$section]['pages']);
        return redirect("/docs/{$section}/{$first}");
    }

    public function page(string $section, string $page)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $doc = DocRegistry::page($section, $page);
        abort_unless($doc, 404);
        [$prev, $next] = DocRegistry::prevNext($section, $page);
        return view('docs.page', [
            'sections' => DocRegistry::sections(),
            'section' => $section,
            'doc' => $doc,
            'prev' => $prev,
            'next' => $next,
        ]);
    }

    public function search(Request $request)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $q = (string) $request->input('q', '');
        return view('docs.search', [
            'sections' => DocRegistry::sections(),
            'q' => $q,
            'hits' => DocRegistry::search($q),
        ]);
    }

    /** Autosuggest JSON untuk overlay pencarian (Ctrl+K). */
    public function suggest(Request $request)
    {
        if ($r = $this->guard()) {
            return response()->json(['data' => []]);
        }
        $data = collect(DocRegistry::search((string) $request->input('q', '')))
            ->take(8)
            ->map(fn ($h) => [
                'title' => $h['page']['title'] ?? '',
                'url' => $h['page']['url'] ?? '/docs',
                'module' => $h['page']['module'] ?? '',
                'category' => DocRegistry::categoryFor($h['page']['section'] ?? ''),
            ])->values();
        return response()->json(['data' => $data]);
    }

    /** Health dashboard internal (superadmin): kelengkapan dokumentasi. */
    public function health()
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403);
        }
        $pages = DocRegistry::allPages();
        $noShot = $noPerm = $badRelated = [];
        foreach ($pages as $p) {
            if (empty($p['shot'])) {
                $noShot[] = $p['url'];
            } elseif (!file_exists(public_path('docs-assets/screenshots/' . ltrim($p['shot'], '/')))) {
                $noShot[] = $p['url'] . ' (file hilang: ' . $p['shot'] . ')';
            }
            if (empty($p['permission'])) {
                $noPerm[] = $p['url'];
            }
            foreach ($p['related'] ?? [] as [$label, $url]) {
                if (!str_starts_with($url, '/docs/')) {
                    continue;
                }
                $parts = explode('/', trim($url, '/'));
                if (($parts[0] ?? '') !== 'docs' || !DocRegistry::page($parts[1] ?? '', $parts[2] ?? '')) {
                    $badRelated[] = $p['url'] . ' → ' . $url;
                }
            }
        }
        $badMapping = [];
        foreach (DocRegistry::routeDocMap() as $route => $url) {
            $parts = explode('/', trim($url, '/'));
            if (!\Illuminate\Support\Facades\Route::has($route) || !DocRegistry::page($parts[1] ?? '', $parts[2] ?? '')) {
                $badMapping[] = $route . ' → ' . $url;
            }
        }
        return view('docs.health', [
            'sections' => DocRegistry::sections(),
            'total' => count($pages),
            'noShot' => $noShot,
            'noPerm' => $noPerm,
            'badRelated' => $badRelated,
            'badMapping' => $badMapping,
        ]);
    }

    public function sitemap()
    {
        $pages = DocRegistry::allPages();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= '  <url><loc>' . url('/docs') . '</loc></url>' . "\n";
        foreach ($pages as $p) {
            $xml .= '  <url><loc>' . url($p['url']) . '</loc></url>' . "\n";
        }
        $xml .= '</urlset>';
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
