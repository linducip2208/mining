@extends('layouts.docs')

@section('meta-title', 'Cari: ' . $q)

@section('breadcrumb')
<span class="mx-1" aria-hidden="true">/</span><span class="text-slate-700 dark:text-slate-200 font-medium" aria-current="page">Pencarian</span>
@endsection

@section('content')
<h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-50">Hasil pencarian</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kata kunci: "<strong>{{ $q }}</strong>" — {{ count($hits) }} hasil</p>

@if (mb_strlen(trim($q)) < 2)
<p class="text-sm text-slate-400 mt-4">Ketik minimal 2 karakter, atau tekan <kbd class="px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 font-sans">Ctrl K</kbd> untuk pencarian cepat.</p>
@elseif (empty($hits))
<div class="mt-4"><x-ui.empty-state icon="search" title="Tidak ditemukan" body="Coba kata lain, mis. timbangan, invoice, jurnal, cuti." /></div>
@else
@php
    $grouped = collect($hits)->groupBy(fn ($h) => \App\Docs\DocRegistry::categoryFor($h['page']['section']));
    $words = preg_split('/\s+/', trim($q));
    $hl = function ($text) use ($words) {
        $out = e($text);
        foreach ($words as $w) {
            if (mb_strlen($w) < 2) continue;
            $out = preg_replace('/(' . preg_quote($w, '/') . ')/iu', '<mark class="bg-amber-200 dark:bg-amber-500/40 rounded-sm px-0.5">$1</mark>', $out);
        }
        return $out;
    };
@endphp
<div class="mt-4 space-y-6">
    @foreach ($grouped as $cat => $items)
    <section aria-label="Kategori {{ $cat }}">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">{{ $cat }} ({{ $items->count() }})</h2>
        <div class="space-y-2">
            @foreach ($items as ['page' => $p])
            <a href="{{ $p['url'] }}" class="block bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-slate-700/60 p-4 hover:border-amber-400">
                <div class="text-sm font-semibold">{!! $hl($p['title']) !!}</div>
                <div class="text-xs text-slate-400 mt-0.5">{{ $p['module'] ?? '' }}</div>
                <div class="text-[13px] text-slate-600 dark:text-slate-300 mt-1">{!! $hl(\Illuminate\Support\Str::limit($p['purpose'] ?? '', 140)) !!}</div>
            </a>
            @endforeach
        </div>
    </section>
    @endforeach
</div>
<script>
try {
    var r = JSON.parse(localStorage.getItem('docs-recent') || '[]').filter(function (x) { return x.title !== @json($q); });
    r.unshift({ url: location.pathname + location.search, title: 'Cari: ' + @json($q) });
    localStorage.setItem('docs-recent', JSON.stringify(r.slice(0, 6)));
} catch (e) {}
</script>
@endif
@endsection
