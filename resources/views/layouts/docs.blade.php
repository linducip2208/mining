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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            aside.docs-sidebar, header.docs-topbar .no-print, .docs-toc, .docs-pager { display: none !important; }
            main { margin: 0 !important; max-width: 100% !important; }
        }
        .docs-content h2 { font-size: 1.1rem; font-weight: 700; margin: 1.5rem 0 .5rem; color: #1e293b; }
        .docs-content ol.steps { list-style: decimal; padding-left: 1.4rem; display: grid; gap: .4rem; }
        .docs-content table.info { width: 100%; font-size: .85rem; }
        .docs-content table.info td { padding: .4rem .6rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .docs-content table.info td:first-child { font-weight: 600; white-space: nowrap; color: #475569; }
    </style>
    @stack('head')
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">
<div class="min-h-screen flex">
    <aside class="docs-sidebar fixed inset-y-0 left-0 z-30 w-72 -translate-x-full lg:translate-x-0 transition-transform duration-200 bg-slate-900 text-slate-300 flex flex-col" id="docsSidebar">
        <a href="/docs" class="h-16 flex items-center gap-3 px-5 border-b border-slate-800 shrink-0">
            <div class="w-9 h-9 rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-900">M</div>
            <div>
                <div class="font-bold text-white text-sm tracking-wide">MINING ERP · DOCS</div>
                <div class="text-[10px] text-slate-500">Tutorial & Panduan Resmi</div>
            </div>
        </a>
        <form action="{{ route('docs.search') }}" method="GET" class="p-3 shrink-0">
            <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="Cari tutorial… (cth. timbangan)"
                   class="w-full px-3 py-2 text-sm rounded-lg bg-slate-800 border border-slate-700 text-white placeholder-slate-500 focus:border-amber-400 outline-none">
        </form>
        <nav class="flex-1 overflow-y-auto py-2 px-2 space-y-1 text-[13px]">
            @foreach ($sections as $sSlug => $s)
                <div x-data="{ open: {{ (isset($section) && $section === $sSlug) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-slate-800 text-slate-400 uppercase text-[11px] tracking-wider font-semibold">
                        {{ $s['title'] }}
                        <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-0.5">
                        @foreach ($s['pages'] as $pSlug => $p)
                            @php $isActive = isset($section, $doc) && $section === $sSlug && $doc['slug'] === $pSlug; @endphp
                            <a href="/docs/{{ $sSlug }}/{{ $pSlug }}"
                               class="flex items-center px-3 py-1.5 rounded-md {{ $isActive ? 'bg-slate-800 text-white font-medium' : 'hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-600 mr-2.5 {{ $isActive ? 'bg-amber-500' : '' }}"></span>
                                {{ $p['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
        <div class="p-4 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between">
            <span>Mining ERP Docs</span>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="hover:text-white">← Aplikasi</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 lg:ml-72">
        <header class="docs-topbar h-16 bg-white border-b border-slate-200 sticky top-0 z-20 flex items-center gap-3 px-4 lg:px-8">
            <button class="lg:hidden p-2 rounded-md hover:bg-slate-100" onclick="document.getElementById('docsSidebar').classList.toggle('-translate-x-full')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <nav class="text-sm text-slate-400 truncate">
                <a href="/docs" class="hover:text-amber-600">Docs</a>
                @yield('breadcrumb')
            </nav>
            <div class="ml-auto flex items-center gap-2 no-print">
                <button onclick="window.print()" class="px-3 py-1.5 text-xs rounded-lg bg-slate-100 hover:bg-slate-200">Cetak</button>
            </div>
        </header>
        <main class="flex-1 p-4 lg:p-8 w-full max-w-5xl">
            @yield('content')
        </main>
    </div>
</div>

{{-- Lightbox viewer --}}
<div id="docsLightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85 p-4" onclick="if(event.target===this)DocsLB.close()">
    <button onclick="DocsLB.close()" class="absolute top-4 right-5 text-white text-3xl leading-none">×</button>
    <button onclick="DocsLB.prev(event)" id="docsLbPrev" class="absolute left-3 top-1/2 -translate-y-1/2 text-white text-4xl px-3">‹</button>
    <img id="docsLbImg" src="" alt="screenshot" class="max-h-[88vh] max-w-[92vw] rounded-lg shadow-2xl object-contain">
    <button onclick="DocsLB.next(event)" id="docsLbNext" class="absolute right-3 top-1/2 -translate-y-1/2 text-white text-4xl px-3">›</button>
    <div id="docsLbCap" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-slate-300 text-xs"></div>
</div>
<script>
(function () {
    let imgs = [], idx = 0;
    function collect() {
        imgs = Array.from(document.querySelectorAll('img.docs-shot')).map(el => ({ src: el.src, cap: el.alt || '' }));
    }
    document.addEventListener('click', function (e) {
        const t = e.target.closest('img.docs-shot');
        if (!t) return;
        collect();
        idx = Math.max(0, imgs.findIndex(i => i.src === t.src));
        show();
    });
    function show() {
        if (!imgs.length) return;
        const lb = document.getElementById('docsLightbox');
        document.getElementById('docsLbImg').src = imgs[idx].src;
        document.getElementById('docsLbCap').textContent = (idx + 1) + ' / ' + imgs.length + ' — ' + imgs[idx].cap;
        lb.classList.remove('hidden'); lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function step(d, e) {
        if (e) e.stopPropagation();
        idx = (idx + d + imgs.length) % imgs.length;
        show();
    }
    document.addEventListener('keydown', function (e) {
        const lb = document.getElementById('docsLightbox');
        if (lb.classList.contains('hidden')) return;
        if (e.key === 'Escape') DocsLB.close();
        if (e.key === 'ArrowRight') step(1);
        if (e.key === 'ArrowLeft') step(-1);
    });
    window.DocsLB = {
        close: function () {
            const lb = document.getElementById('docsLightbox');
            lb.classList.add('hidden'); lb.classList.remove('flex');
            document.body.style.overflow = '';
        },
        next: function (e) { step(1, e); },
        prev: function (e) { step(-1, e); }
    };
})();
</script>
@stack('scripts')
</body>
</html>
