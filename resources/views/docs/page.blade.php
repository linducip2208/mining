@extends('layouts.docs')

@section('meta-title', $doc['title'])
@section('meta-desc', \Illuminate\Support\Str::limit($doc['purpose'] ?? '', 160))

@section('breadcrumb')
<span class="mx-1" aria-hidden="true">/</span><span>{{ $sections[$section]['title'] }}</span>
<span class="mx-1" aria-hidden="true">/</span><span class="text-slate-700 dark:text-slate-200 font-medium" aria-current="page">{{ $doc['title'] }}</span>
@endsection

@section('content')
@php
    use App\Docs\DocRegistry;
    $shotUrl = DocRegistry::screenshotUrl($doc);
    $shotPath = !empty($doc['shot']) ? public_path('docs-assets/screenshots/' . ltrim($doc['shot'], '/')) : null;
    $shotExists = $shotPath && file_exists($shotPath);
    $appRoute = DocRegistry::appRouteForDoc($doc['url']);
    $toc = [];
    if (!empty($doc['steps'])) $toc[] = ['langkah', 'Langkah-langkah'];
    if (!empty($doc['fields'])) $toc[] = ['field', 'Penjelasan Field'];
    if (!empty($doc['buttons'])) $toc[] = ['tombol', 'Penjelasan Tombol'];
    if (!empty($doc['workflow'])) $toc[] = ['alur', 'Alur / Status'];
    if (!empty($doc['expected'])) $toc[] = ['hasil', 'Hasil yang Diharapkan'];
    if (!empty($doc['faqs'])) $toc[] = ['faq', 'Tanya Jawab'];
    if (!empty($doc['errors'])) $toc[] = ['error', 'Error Umum'];
    if (!empty($doc['tips'])) $toc[] = ['tips', 'Tips'];
    if (!empty($doc['related'])) $toc[] = ['terkait', 'Modul Terkait'];
@endphp

<div class="xl:flex xl:gap-8 items-start">
<div class="min-w-0 flex-1">
    <h1 class="text-2xl lg:text-[28px] font-bold tracking-tight text-slate-800 dark:text-slate-50">{{ $doc['title'] }}</h1>

    {{-- Metadata --}}
    <dl class="mt-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 text-xs" aria-label="Metadata tutorial">
        <div class="rounded-lg bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700/60 px-3 py-2">
            <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Modul</dt>
            <dd class="font-medium mt-0.5 truncate" title="{{ $doc['module'] ?? '' }}">{{ $doc['module'] ?? '—' }}</dd>
        </div>
        <div class="rounded-lg bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700/60 px-3 py-2">
            <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Permission</dt>
            <dd class="font-mono mt-0.5 truncate" title="{{ $doc['permission'] ?? '' }}">{{ $doc['permission'] ?? '—' }}</dd>
        </div>
        <div class="rounded-lg bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700/60 px-3 py-2">
            <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Peran</dt>
            <dd class="font-medium mt-0.5">{{ DocRegistry::roleFor($doc['permission'] ?? null) }}</dd>
        </div>
        <div class="rounded-lg bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700/60 px-3 py-2">
            <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Tingkat</dt>
            <dd class="font-medium mt-0.5">{{ DocRegistry::difficultyFor($doc) }}</dd>
        </div>
        <div class="rounded-lg bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700/60 px-3 py-2">
            <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Menu</dt>
            <dd class="font-medium mt-0.5 truncate" title="{{ $doc['nav'] ?? '' }}">{{ $doc['nav'] ?? '—' }}</dd>
        </div>
    </dl>

    {{-- Page actions --}}
    <div class="mt-3 flex flex-wrap gap-2 no-print">
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 hover:border-slate-300"><x-ui.icon name="printer" class="w-3.5 h-3.5" />Cetak</button>
        <button data-copy-link data-url="{{ url($doc['url']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 hover:border-slate-300"><x-ui.icon name="copy" class="w-3.5 h-3.5" />Salin Tautan</button>
        @if ($appRoute && \Illuminate\Support\Facades\Route::has($appRoute))
        <a href="{{ route($appRoute) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-semibold"><x-ui.icon name="external" class="w-3.5 h-3.5" />Buka di Aplikasi</a>
        @endif
        <button data-report-issue data-title="{{ $doc['title'] }}" data-url="{{ url($doc['url']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5"><x-ui.icon name="message" class="w-3.5 h-3.5" />Laporkan Masalah Docs</button>
    </div>
    <div data-copy-toast class="hidden mt-2 text-xs text-green-600 dark:text-green-400" role="status"></div>

    @if (!empty($doc['purpose']))
    <p class="mt-4 text-[15px] leading-relaxed text-slate-700 dark:text-slate-300">{{ $doc['purpose'] }}</p>
    @endif

    @if ($shotUrl && $shotExists)
    <figure class="mt-5">
        <img src="{{ $shotUrl }}" alt="Screenshot: {{ $doc['title'] }}" loading="lazy" decoding="async"
             class="docs-shot w-full rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm cursor-zoom-in hover:shadow-md transition">
        <figcaption class="text-xs text-slate-400 mt-1.5">Klik gambar untuk memperbesar, zoom, atau layar penuh. Screenshot nyata dari aplikasi (data demo).</figcaption>
    </figure>
    @endif

    <div class="docs-prose docs-content text-slate-700 dark:text-slate-300">
    @if (!empty($doc['steps']))
        <h2 id="langkah">Langkah-langkah</h2>
        <ol class="steps">
            @foreach ($doc['steps'] as $s)
            <li>{{ $s }}</li>
            @endforeach
        </ol>
    @endif

    @if (!empty($doc['fields']))
        <h2 id="field">Penjelasan Field</h2>
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-slate-700/60 overflow-hidden not-prose">
            <table class="info w-full text-sm">
                @foreach ($doc['fields'] as $k => $v)
                <tr class="border-b border-slate-100 dark:border-slate-700/50 last:border-0"><td class="px-4 py-2.5 font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap align-top">{{ $k }}</td><td class="px-4 py-2.5">{{ $v }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    @if (!empty($doc['buttons']))
        <h2 id="tombol">Penjelasan Tombol</h2>
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-slate-700/60 overflow-hidden not-prose">
            <table class="info w-full text-sm">
                @foreach ($doc['buttons'] as $k => $v)
                <tr class="border-b border-slate-100 dark:border-slate-700/50 last:border-0"><td class="px-4 py-2.5 font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap align-top">{{ $k }}</td><td class="px-4 py-2.5">{{ $v }}</td></tr>
                @endforeach
            </table>
        </div>
    @endif
    </div>

    @if (!empty($doc['workflow']))
    <div id="alur" class="mt-6 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 rounded-xl p-4 scroll-mt-24">
        <div class="text-xs font-bold uppercase tracking-wider text-indigo-500">Alur / Status</div>
        <div class="text-sm text-slate-700 dark:text-slate-200 mt-1 font-mono">{{ $doc['workflow'] }}</div>
    </div>
    @endif

    @if (!empty($doc['expected']))
    <div id="hasil" class="mt-4 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 rounded-xl p-4 scroll-mt-24">
        <div class="text-xs font-bold uppercase tracking-wider text-green-600 dark:text-green-300">Hasil yang Diharapkan</div>
        <div class="text-sm text-slate-700 dark:text-slate-200 mt-1">{{ $doc['expected'] }}</div>
    </div>
    @endif

    @if (!empty($doc['faqs']))
    <div class="docs-content mt-6">
        <h2 id="faq" class="scroll-mt-24">Tanya Jawab</h2>
        <div class="space-y-2">
            @foreach ($doc['faqs'] as [$q, $a])
            <details class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-slate-700/60 px-4 py-3">
                <summary class="text-sm font-semibold cursor-pointer">{{ $q }}</summary>
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1.5">{{ $a }}</p>
            </details>
            @endforeach
        </div>
    </div>
    @endif

    @if (!empty($doc['errors']))
    <div class="mt-6">
        <h2 id="error" class="text-[17px] font-bold mb-2 scroll-mt-24">Error Umum</h2>
        <div class="space-y-2">
            @foreach ($doc['errors'] as $e => $sol)
            <div class="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 rounded-xl px-4 py-3">
                <div class="text-sm font-semibold text-red-700 dark:text-red-300">{{ $e }}</div>
                <div class="text-sm text-slate-600 dark:text-slate-300 mt-0.5">{{ $sol }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if (!empty($doc['tips']))
    <div class="mt-6 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-xl p-4">
        <div class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-300">Tips</div>
        <ul class="list-disc pl-5 mt-1 space-y-0.5 text-sm text-slate-700 dark:text-slate-200">
            @foreach ($doc['tips'] as $t)
            <li>{{ $t }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (!empty($doc['related']))
    <div class="mt-6">
        <h2 id="terkait" class="text-[17px] font-bold mb-2 scroll-mt-24">Tutorial Terkait</h2>
        <div class="flex flex-wrap gap-2">
            @foreach ($doc['related'] as [$label, $url])
            <a href="{{ $url }}" class="text-xs px-3 py-1.5 rounded-full bg-white dark:bg-navy-800 border border-slate-200 dark:border-slate-700 hover:border-amber-400">{{ $label }} →</a>
            @endforeach
        </div>
    </div>
    @endif

    <div class="docs-pager grid sm:grid-cols-2 gap-3 mt-8 no-print">
        <div>
            @if ($prev)
            <a href="{{ $prev['url'] }}" rel="prev" class="block bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-slate-700/60 p-3 hover:border-amber-400">
                <div class="text-[11px] uppercase text-slate-400">← Sebelumnya</div>
                <div class="text-sm font-semibold">{{ $prev['title'] }}</div>
            </a>
            @endif
        </div>
        <div class="sm:text-right">
            @if ($next)
            <a href="{{ $next['url'] }}" rel="next" class="block bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-slate-700/60 p-3 hover:border-amber-400">
                <div class="text-[11px] uppercase text-slate-400">Berikutnya →</div>
                <div class="text-sm font-semibold">{{ $next['title'] }}</div>
            </a>
            @endif
        </div>
    </div>
</div>

{{-- TOC kanan (desktop) --}}
@if ($toc)
<aside class="docs-toc hidden xl:block w-56 flex-none sticky top-24 max-h-[70vh] overflow-y-auto nice-scroll" aria-label="Daftar isi">
    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Di halaman ini</div>
    <ol class="space-y-1 text-[13px]">
        @foreach ($toc as [$id, $label])
        <li><a href="#{{ $id }}" class="block px-2 py-1 rounded text-slate-500 dark:text-slate-400 hover:text-amber-600 hover:bg-slate-100 dark:hover:bg-white/5">{{ $label }}</a></li>
        @endforeach
    </ol>
    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-700/60 text-xs text-slate-400">
        <div class="font-semibold mb-1">Screenshot</div>
        {{ $shotExists ? 'Tersedia ✓' : 'Belum ada' }}
    </div>
</aside>
@endif
</div>

<script>
(function () {
    function toast(msg) {
        var el = document.querySelector('[data-copy-toast]');
        if (!el) return;
        el.textContent = msg; el.classList.remove('hidden');
        setTimeout(function () { el.classList.add('hidden'); }, 2600);
    }
    document.querySelectorAll('[data-copy-link]').forEach(function (b) {
        b.addEventListener('click', function () {
            var url = b.dataset.url;
            (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(
                function () { toast('Tautan disalin: ' + url); },
                function () { toast(url); });
        });
    });
    document.querySelectorAll('[data-report-issue]').forEach(function (b) {
        b.addEventListener('click', function () {
            var text = '[Docs Issue] ' + b.dataset.title + '\nURL: ' + b.dataset.url + '\n\nDeskripsi masalah:\n- ';
            (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(
                function () { toast('Template laporan disalin — tempel ke issue tracker grup Anda.'); },
                function () { toast(b.dataset.url); });
        });
    });
})();
</script>
@endsection
