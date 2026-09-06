<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('meta-title', 'Dokumentasi') · Mining ERP Docs</title>
    <meta name="description" content="@yield('meta-desc', 'Pusat dokumentasi dan tutorial resmi Mining ERP: panduan seluruh modul, alur end-to-end, dan troubleshooting.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('meta-title', 'Dokumentasi') · Mining ERP Docs">
    <meta property="og:description" content="@yield('meta-desc', 'Pusat dokumentasi dan tutorial resmi Mining ERP.')">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛏️</text></svg>">
    <script>
        try {
            if (localStorage.getItem('docs-theme') === 'dark' || (!localStorage.getItem('docs-theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased bg-slate-100 dark:bg-navy-950 text-slate-800 dark:text-slate-200">
<a href="#docsMain" class="sr-only focus:not-sr-only focus:absolute focus:z-[70] focus:px-4 focus:py-2 focus:bg-amber-500 focus:text-white focus:rounded-md focus:m-2">Lewati ke konten</a>
<div class="min-h-screen flex">
    <div id="docsOverlay" class="fixed inset-0 z-20 bg-slate-900/50 hidden lg:hidden"></div>
    <aside id="docsSidebar" aria-label="Navigasi dokumentasi"
        class="docs-sidebar fixed inset-y-0 left-0 z-30 w-72 -translate-x-full lg:translate-x-0 transition-transform duration-200 bg-navy-900 dark:bg-navy-950 text-slate-300 flex flex-col border-r border-white/5">
        <a href="/docs" class="h-16 flex items-center gap-3 px-5 border-b border-white/10 shrink-0" aria-label="Beranda dokumentasi">
            <div class="w-9 h-9 rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-900" aria-hidden="true">M</div>
            <div>
                <div class="font-bold text-white text-sm tracking-wide">MINING ERP · DOCS</div>
                <div class="text-[10px] text-slate-500">Tutorial & Panduan Resmi</div>
            </div>
        </a>
        <div class="p-3 shrink-0">
            <button type="button" data-docs-search
                class="w-full flex items-center gap-2 px-3 py-2 text-sm rounded-lg bg-white/5 border border-white/10 text-slate-400 hover:border-amber-400/60 hover:text-slate-200">
                <x-ui.icon name="search" class="w-4 h-4" />
                <span class="flex-1 text-left">Cari tutorial…</span>
                <kbd class="text-[10px] px-1.5 py-0.5 rounded border border-white/10 font-sans">Ctrl K</kbd>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto nice-scroll py-2 px-2 space-y-0.5 text-[13px]" aria-label="Daftar modul">
            @foreach (\App\Docs\DocRegistry::groups() as $gName => $gSections)
                @php
                    $gVisible = collect($gSections)->filter(fn ($s) => isset($sections[$s]));
                    if ($gVisible->isEmpty()) continue;
                    $gActive = isset($section) && $gVisible->contains($section);
                    $gIcon = \App\Docs\DocRegistry::sectionMeta($gVisible->first())['icon'];
                @endphp
                <div class="pt-3 first:pt-1">
                    <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500" aria-hidden="true">{{ $gName }}</div>
                    @foreach ($gVisible as $sSlug)
                    @php $s = $sections[$sSlug]; @endphp
                    <div x-data="{ open: {{ (isset($section) && $section === $sSlug) ? 'true' : 'false' }} }" x-init="try { const v = localStorage.getItem('docs-nav-{{ $sSlug }}'); if (v !== null) open = (v === '1'); } catch(e){}" @click="setTimeout(() => { try { localStorage.setItem('docs-nav-{{ $sSlug }}', open ? '1' : '0'); } catch(e){} }, 50)">
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg {{ (isset($section) && $section === $sSlug) ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            <x-ui.icon :name="\App\Docs\DocRegistry::sectionMeta($sSlug)['icon']" class="w-4 h-4 flex-none {{ (isset($section) && $section === $sSlug) ? 'text-amber-400' : 'text-slate-500' }}" />
                            <span class="flex-1 text-left truncate font-medium">{{ $s['title'] }}</span>
                            <span class="text-[10px] text-slate-500">{{ count($s['pages']) }}</span>
                            <svg class="w-3 h-3 flex-none transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-0.5 ml-4 pl-2 border-l border-white/10 space-y-0.5">
                            @foreach ($s['pages'] as $pSlug => $p)
                                @php $isActive = isset($section, $doc) && $section === $sSlug && $doc['slug'] === $pSlug; @endphp
                                <a href="/docs/{{ $sSlug }}/{{ $pSlug }}" @if($isActive) aria-current="page" @endif
                                   class="block px-3 py-1.5 rounded-md text-[13px] truncate {{ $isActive ? 'bg-amber-500/15 text-amber-300 font-semibold' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    {{ $p['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="p-4 border-t border-white/10 text-[11px] text-slate-500 flex items-center justify-between">
            <span>{{ count(\App\Docs\DocRegistry::allPages()) }} tutorial</span>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="hover:text-white">← Aplikasi</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-72">
        <header class="docs-topbar h-16 bg-white/90 dark:bg-navy-900/90 backdrop-blur border-b border-slate-200 dark:border-slate-700/60 sticky top-0 z-20 flex items-center gap-3 px-4 lg:px-8">
            <button class="lg:hidden p-2 rounded-md hover:bg-slate-100 dark:hover:bg-white/5" data-docs-menu aria-label="Buka navigasi dokumentasi">
                <x-ui.icon name="menu" class="w-5 h-5" />
            </button>
            <nav class="text-sm text-slate-400 dark:text-slate-500 truncate" aria-label="Breadcrumb">
                <a href="/docs" class="hover:text-amber-600">Docs</a>
                @yield('breadcrumb')
            </nav>
            <div class="ml-auto flex items-center gap-1.5 no-print">
                <button type="button" data-docs-search title="Cari (Ctrl+K)" aria-label="Cari dokumentasi"
                    class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 text-slate-500"><x-ui.icon name="search" class="w-[18px] h-[18px]" /></button>
                <button type="button" data-docs-theme title="Mode gelap / terang" aria-label="Alihkan mode gelap"
                    class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 text-slate-500">
                    <span class="hidden dark:inline"><x-ui.icon name="sun" class="w-[18px] h-[18px]" /></span>
                    <span class="dark:hidden"><x-ui.icon name="moon" class="w-[18px] h-[18px]" /></span>
                </button>
                <button type="button" onclick="window.print()" class="hidden sm:inline-flex px-3 py-1.5 text-xs rounded-lg bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10">Cetak Halaman</button>
            </div>
        </header>
        <main id="docsMain" class="flex-1 p-4 lg:p-8 w-full max-w-6xl" tabindex="-1">
            @yield('content')
            <footer class="mt-12 pt-6 border-t border-slate-200 dark:border-slate-700/60 text-xs text-slate-400 flex flex-wrap gap-x-4 gap-y-1">
                <span>Mining ERP Documentation</span>
                <a href="/docs/troubleshooting/common" class="hover:text-amber-600">Laporkan masalah docs</a>
                <a href="/docs/sitemap.xml" class="hover:text-amber-600">Sitemap</a>
            </footer>
        </main>
    </div>
</div>

{{-- Search overlay (autosuggest + recent + Ctrl+K) --}}
<div id="docsSearchOv" class="hidden fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Pencarian dokumentasi">
    <div class="absolute inset-0 bg-slate-900/50" data-docs-search-close></div>
    <div class="relative mx-auto mt-20 w-[min(38rem,92vw)] bg-white dark:bg-navy-800 rounded-card shadow-pop border border-slate-200 dark:border-slate-700 overflow-hidden animate-fadeup">
        <div class="flex items-center gap-2 px-4 border-b border-slate-100 dark:border-slate-700/60">
            <x-ui.icon name="search" class="w-4 h-4 text-slate-400" />
            <input id="docsSearchInput" type="search" autocomplete="off" placeholder="Cari fitur, tutorial, menu, error…"
                class="flex-1 py-3.5 text-sm bg-transparent outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400" aria-label="Kata kunci pencarian">
            <kbd class="text-[10px] px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 text-slate-400">ESC</kbd>
        </div>
        <div id="docsSearchRes" class="max-h-[55vh] overflow-y-auto nice-scroll p-2 text-sm"></div>
    </div>
</div>

{{-- Lightbox: click, zoom, fullscreen, prev/next, caption, keyboard --}}
<div id="docsLightbox" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label="Penampil screenshot">
    <div class="absolute top-0 inset-x-0 flex items-center justify-between p-4 text-white">
        <div id="docsLbCap" class="text-xs text-slate-300 truncate pr-4"></div>
        <div class="flex items-center gap-1">
            <button data-lb="zin" class="px-3 py-1.5 rounded-lg hover:bg-white/10 text-xl leading-none" aria-label="Perbesar">+</button>
            <button data-lb="zout" class="px-3 py-1.5 rounded-lg hover:bg-white/10 text-xl leading-none" aria-label="Perkecil">−</button>
            <button data-lb="full" class="px-3 py-1.5 rounded-lg hover:bg-white/10 text-sm" aria-label="Layar penuh">⛶</button>
            <button data-lb="close" class="px-3 py-1.5 rounded-lg hover:bg-white/10 text-2xl leading-none" aria-label="Tutup">×</button>
        </div>
    </div>
    <button data-lb="prev" class="absolute left-2 top-1/2 -translate-y-1/2 text-white text-4xl px-3 py-6 hover:bg-white/10 rounded-lg" aria-label="Gambar sebelumnya">‹</button>
    <div id="docsLbWrap" class="overflow-auto nice-scroll max-h-[86vh] max-w-[94vw] flex items-center justify-center">
        <img id="docsLbImg" src="" alt="screenshot" class="rounded-lg shadow-2xl object-contain transition-transform duration-150" style="max-height:82vh">
    </div>
    <button data-lb="next" class="absolute right-2 top-1/2 -translate-y-1/2 text-white text-4xl px-3 py-6 hover:bg-white/10 rounded-lg" aria-label="Gambar berikutnya">›</button>
</div>

<script>
(function () {
    'use strict';
    /* ---------- mobile drawer ---------- */
    var sb = document.getElementById('docsSidebar'), ov = document.getElementById('docsOverlay');
    function closeSb() { sb.classList.add('-translate-x-full'); ov.classList.add('hidden'); }
    document.querySelectorAll('[data-docs-menu]').forEach(function (b) {
        b.addEventListener('click', function () { sb.classList.remove('-translate-x-full'); ov.classList.remove('hidden'); });
    });
    ov.addEventListener('click', closeSb);

    /* ---------- theme ---------- */
    document.querySelectorAll('[data-docs-theme]').forEach(function (b) {
        b.addEventListener('click', function () {
            var dark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('docs-theme', dark ? 'dark' : 'light'); } catch (e) {}
        });
    });

    /* ---------- search overlay: autosuggest + keyboard + recent ---------- */
    var sov = document.getElementById('docsSearchOv'), inp = document.getElementById('docsSearchInput'),
        res = document.getElementById('docsSearchRes'), items = [], sel = -1, t = null;
    function openS() { sov.classList.remove('hidden'); inp.focus(); renderRecent(); }
    function closeS() { sov.classList.add('hidden'); sel = -1; }
    document.querySelectorAll('[data-docs-search]').forEach(function (b) { b.addEventListener('click', openS); });
    document.querySelector('[data-docs-search-close]').addEventListener('click', closeS);
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); sov.classList.contains('hidden') ? openS() : closeS(); }
        if (e.key === 'Escape' && !sov.classList.contains('hidden')) closeS();
        if (sov.classList.contains('hidden')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(sel + 1, items.length - 1); paint(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(sel - 1, 0); paint(); }
        if (e.key === 'Enter') {
            if (sel >= 0 && items[sel]) { go(items[sel].url, items[sel].title); }
            else if (inp.value.trim().length >= 2) { go('/docs/search?q=' + encodeURIComponent(inp.value.trim()), inp.value.trim()); }
        }
    });
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
    function hi(text, q) {
        text = String(text || '');
        if (!q) return esc(text);
        try { return esc(text).replace(new RegExp('(' + q.trim().split(/\s+/).map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|') + ')', 'ig'), '<mark class="bg-amber-200 dark:bg-amber-500/40 rounded-sm px-0.5">$1</mark>'); }
        catch (e) { return esc(text); }
    }
    function paint() {
        var q = inp.value.trim();
        res.innerHTML = items.map(function (it, i) {
            return '<a href="' + it.url + '" data-hit="' + i + '" class="flex items-center gap-3 px-3 py-2.5 rounded-lg ' + (i === sel ? 'bg-amber-50 dark:bg-amber-500/10' : 'hover:bg-slate-50 dark:hover:bg-white/5') + '">'
                + '<span class="flex-none text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/10 text-slate-500">' + esc(it.category || '') + '</span>'
                + '<span class="min-w-0"><span class="block text-sm font-medium truncate">' + hi(it.title, q) + '</span>'
                + '<span class="block text-xs text-slate-400 truncate">' + esc(it.module || '') + '</span></span></a>';
        }).join('') || '<div class="px-4 py-6 text-sm text-slate-400 text-center">Tidak ada hasil — tekan Enter untuk pencarian penuh.</div>';
        res.querySelectorAll('[data-hit]').forEach(function (a) {
            a.addEventListener('click', function () { go(items[+a.dataset.hit].url, items[+a.dataset.hit].title); });
        });
    }
    function go(url, title) {
        try {
            var r = JSON.parse(localStorage.getItem('docs-recent') || '[]').filter(function (x) { return x.url !== url; });
            r.unshift({ url: url, title: title }); localStorage.setItem('docs-recent', JSON.stringify(r.slice(0, 6)));
        } catch (e) {}
        location.href = url;
    }
    function renderRecent() {
        var q = inp.value.trim();
        if (q.length >= 2) return;
        var r = [];
        try { r = JSON.parse(localStorage.getItem('docs-recent') || '[]'); } catch (e) {}
        items = []; sel = -1;
        res.innerHTML = (r.length ? '<div class="px-3 py-1.5 text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Terakhir dicari</div>' : '')
            + r.map(function (x, i) {
                return '<a href="' + x.url + '" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 text-sm">' +
                    '<span class="text-slate-300">↺</span><span class="truncate">' + esc(x.title) + '</span></a>';
            }).join('')
            + '<div class="px-3 py-1.5 text-[11px] uppercase tracking-wider text-slate-400 font-semibold mt-1">Pintasan</div>'
            + [['Mine to Cash', '/docs/workflows/mine-to-cash'], ['Procure to Pay', '/docs/workflows/procure-to-pay'], ['FAQ', '/docs/faq/general']].map(function (x) {
                return '<a href="' + x[1] + '" class="block px-3 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 text-sm">' + x[0] + '</a>';
            }).join('');
    }
    inp.addEventListener('input', function () {
        clearTimeout(t);
        var q = inp.value.trim();
        if (q.length < 2) { renderRecent(); return; }
        t = setTimeout(function () {
            fetch('/docs/suggest?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); }).then(function (j) { items = j.data || []; sel = items.length ? 0 : -1; paint(); })
                .catch(function () { items = []; paint(); });
        }, 160);
    });

    /* ---------- lightbox: zoom + fullscreen + prev/next ---------- */
    var lb = document.getElementById('docsLightbox'), lbImg = document.getElementById('docsLbImg'),
        lbCap = document.getElementById('docsLbCap'), imgs = [], idx = 0, zoom = 1;
    function collect() { imgs = Array.from(document.querySelectorAll('img.docs-shot')).map(function (el) { return { src: el.currentSrc || el.src, cap: el.alt || '' }; }); }
    function show() {
        if (!imgs.length) return;
        lbImg.src = imgs[idx].src; lbImg.style.transform = 'scale(' + zoom + ')';
        lbCap.textContent = (idx + 1) + ' / ' + imgs.length + ' — ' + imgs[idx].cap;
        lb.classList.remove('hidden'); lb.classList.add('flex'); document.body.style.overflow = 'hidden';
    }
    function step(d) { idx = (idx + d + imgs.length) % imgs.length; zoom = 1; show(); }
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-lb]');
        if (b) {
            var a = b.dataset.lb;
            if (a === 'close') window.DocsLB.close();
            if (a === 'prev') step(-1);
            if (a === 'next') step(1);
            if (a === 'zin') { zoom = Math.min(3, zoom + 0.25); show(); }
            if (a === 'zout') { zoom = Math.max(0.5, zoom - 0.25); show(); }
            if (a === 'full') { if (document.fullscreenElement) document.exitFullscreen(); else (lb.requestFullscreen || function () {}).call(lb); }
            return;
        }
        if (e.target === lb) window.DocsLB.close();
        var t = e.target.closest('img.docs-shot');
        if (!t) return;
        collect(); idx = Math.max(0, imgs.findIndex(function (i) { return i.src === (t.currentSrc || t.src); })); zoom = 1; show();
    });
    lbImg.addEventListener('wheel', function (e) { e.preventDefault(); zoom = Math.min(3, Math.max(0.5, zoom + (e.deltaY < 0 ? 0.15 : -0.15))); show(); }, { passive: false });
    document.addEventListener('keydown', function (e) {
        if (lb.classList.contains('hidden')) return;
        if (e.key === 'Escape') window.DocsLB.close();
        if (e.key === 'ArrowRight') step(1);
        if (e.key === 'ArrowLeft') step(-1);
        if (e.key === '+' || e.key === '=') { zoom = Math.min(3, zoom + 0.25); show(); }
        if (e.key === '-') { zoom = Math.max(0.5, zoom - 0.25); show(); }
    });
    window.DocsLB = { close: function () { lb.classList.add('hidden'); lb.classList.remove('flex'); document.body.style.overflow = ''; zoom = 1; }, next: function () { step(1); }, prev: function () { step(-1); } };
})();
</script>
@stack('scripts')
</body>
</html>
