@extends('layouts.docs')

@section('meta-title', $doc['title'])
@section('meta-desc', \Illuminate\Support\Str::limit($doc['purpose'] ?? '', 160))

@section('breadcrumb')
<span class="mx-1">/</span><span>{{ $sections[$section]['title'] }}</span>
<span class="mx-1">/</span><span class="text-slate-700 font-medium">{{ $doc['title'] }}</span>
@endsection

@section('content')
@php
    use App\Docs\DocRegistry;
    $shotUrl = DocRegistry::screenshotUrl($doc);
    $shotPath = !empty($doc['shot']) ? public_path('docs/screenshots/' . ltrim($doc['shot'], '/')) : null;
    $shotExists = $shotPath && file_exists($shotPath);
@endphp

<h1 class="text-2xl font-bold text-slate-800">{{ $doc['title'] }}</h1>

<div class="flex flex-wrap gap-2 mt-2 text-xs">
    <span class="px-2.5 py-1 rounded-full bg-slate-800 text-white">{{ $doc['module'] ?? '' }}</span>
    @if (!empty($doc['permission']) && $doc['permission'] !== '—')
    <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">Izin: {{ $doc['permission'] }}</span>
    @endif
    @if (!empty($doc['nav']))
    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">Menu: {{ $doc['nav'] }}</span>
    @endif
</div>

@if (!empty($doc['purpose']))
<p class="mt-4 text-[15px] leading-relaxed text-slate-700">{{ $doc['purpose'] }}</p>
@endif

@if ($shotUrl && $shotExists)
<figure class="mt-4">
    <img src="{{ $shotUrl }}" alt="Screenshot: {{ $doc['title'] }}" loading="lazy"
         class="docs-shot w-full rounded-xl border border-slate-200 shadow-sm cursor-zoom-in hover:shadow-md transition">
    <figcaption class="text-xs text-slate-400 mt-1.5">Klik gambar untuk memperbesar. Screenshot nyata dari aplikasi (data demo).</figcaption>
</figure>
@endif

@if (!empty($doc['steps']))
<div class="docs-content">
    <h2>Langkah-langkah</h2>
    <ol class="steps text-[15px] text-slate-700">
        @foreach ($doc['steps'] as $s)
        <li>{{ $s }}</li>
        @endforeach
    </ol>
</div>
@endif

@if (!empty($doc['fields']))
<div class="docs-content">
    <h2>Penjelasan Field</h2>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="info">
            @foreach ($doc['fields'] as $k => $v)
            <tr><td>{{ $k }}</td><td class="text-slate-700">{{ $v }}</td></tr>
            @endforeach
        </table>
    </div>
</div>
@endif

@if (!empty($doc['buttons']))
<div class="docs-content">
    <h2>Penjelasan Tombol</h2>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="info">
            @foreach ($doc['buttons'] as $k => $v)
            <tr><td>{{ $k }}</td><td class="text-slate-700">{{ $v }}</td></tr>
            @endforeach
        </table>
    </div>
</div>
@endif

@if (!empty($doc['workflow']))
<div class="mt-6 bg-indigo-50 border border-indigo-200 rounded-xl p-4">
    <div class="text-xs font-bold uppercase tracking-wider text-indigo-500">Alur / Status</div>
    <div class="text-sm text-slate-700 mt-1 font-mono">{{ $doc['workflow'] }}</div>
</div>
@endif

@if (!empty($doc['expected']))
<div class="mt-4 bg-green-50 border border-green-200 rounded-xl p-4">
    <div class="text-xs font-bold uppercase tracking-wider text-green-600">Hasil yang Diharapkan</div>
    <div class="text-sm text-slate-700 mt-1">{{ $doc['expected'] }}</div>
</div>
@endif

@if (!empty($doc['faqs']))
<div class="docs-content">
    <h2>Tanya Jawab</h2>
    <div class="space-y-2">
        @foreach ($doc['faqs'] as [$q, $a])
        <details class="bg-white rounded-xl border border-slate-200 px-4 py-3">
            <summary class="text-sm font-semibold cursor-pointer">{{ $q }}</summary>
            <p class="text-sm text-slate-600 mt-1.5">{{ $a }}</p>
        </details>
        @endforeach
    </div>
</div>
@endif

@if (!empty($doc['errors']))
<div class="mt-6">
    <h2 class="text-[17px] font-bold text-slate-800 mb-2">Error Umum</h2>
    <div class="space-y-2">
        @foreach ($doc['errors'] as $e => $sol)
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
            <div class="text-sm font-semibold text-red-700">{{ $e }}</div>
            <div class="text-sm text-slate-600 mt-0.5">{{ $sol }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if (!empty($doc['tips']))
<div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4">
    <div class="text-xs font-bold uppercase tracking-wider text-amber-600">Tips</div>
    <ul class="list-disc pl-5 mt-1 space-y-0.5 text-sm text-slate-700">
        @foreach ($doc['tips'] as $t)
        <li>{{ $t }}</li>
        @endforeach
    </ul>
</div>
@endif

@if (!empty($doc['related']))
<div class="mt-6">
    <h2 class="text-[17px] font-bold text-slate-800 mb-2">Modul Terkait</h2>
    <div class="flex flex-wrap gap-2">
        @foreach ($doc['related'] as [$label, $url])
        <a href="{{ $url }}" class="text-xs px-3 py-1.5 rounded-full bg-white border border-slate-200 hover:border-amber-400">{{ $label }} →</a>
        @endforeach
    </div>
</div>
@endif

<div class="docs-pager grid grid-cols-2 gap-3 mt-8">
    <div>
        @if ($prev)
        <a href="{{ $prev['url'] }}" class="block bg-white rounded-xl border border-slate-200 p-3 hover:border-amber-400">
            <div class="text-[11px] uppercase text-slate-400">← Sebelumnya</div>
            <div class="text-sm font-semibold">{{ $prev['title'] }}</div>
        </a>
        @endif
    </div>
    <div class="text-right">
        @if ($next)
        <a href="{{ $next['url'] }}" class="block bg-white rounded-xl border border-slate-200 p-3 hover:border-amber-400">
            <div class="text-[11px] uppercase text-slate-400">Berikutnya →</div>
            <div class="text-sm font-semibold">{{ $next['title'] }}</div>
        </a>
        @endif
    </div>
</div>
@endsection
