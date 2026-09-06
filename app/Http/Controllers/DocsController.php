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
