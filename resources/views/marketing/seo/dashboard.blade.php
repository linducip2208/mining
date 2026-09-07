@extends('layouts.app')
@section('title', ' - SEO Dashboard')
@section('content')
<x-ui.page-header title="SEO Dashboard" description="Programmatic SEO engine — capacity {{ number_format($capacity) }} halaman.">
    <x-slot:actions>
        <x-ui.button size="sm" variant="secondary" :href="route('marketing.seo.pages')">Kelola Pages</x-ui.button>
        <x-ui.button size="sm" variant="secondary" :href="route('marketing.seo.catalog')">Katalog</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    @foreach([['Generated', $overview['generated']], ['Indexable', $overview['indexable']], ['Noindex', $overview['noindex']], ['Orphan', $overview['orphans']], ['Dup judul', $overview['duplicate_titles']], ['Thin', $overview['thin_pages']], ['Avg quality', $overview['avg_quality']], ['Capacity', $capacity]] as [$label, $value])
    <div class="dashboard-card p-4"><div class="text-xs font-semibold text-slate-500">{{ $label }}</div><div class="mt-1 text-2xl font-bold">{{ $value }}</div></div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-3">Status</h2>
        @foreach($overview['by_status'] as $status => $count)
        <div class="flex justify-between text-sm py-1.5 border-b border-slate-100"><span>{{ $status }}</span><strong>{{ $count }}</strong></div>
        @endforeach
    </div>
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-3">Kandidat per rule (dry-run)</h2>
        @foreach($rules['rules'] as $rule => $count)
        <div class="flex justify-between text-sm py-1.5 border-b border-slate-100"><span class="font-mono text-xs">{{ $rule }}</span><strong>{{ number_format($count) }}</strong></div>
        @endforeach
        <div class="flex justify-between text-sm py-1.5 font-semibold"><span>Total kapasitas</span><span>{{ number_format($rules['total']) }} / {{ number_format($capacity) }}</span></div>
        <h2 class="font-semibold mt-4 mb-2">Tier rollout</h2>
        @foreach($tiers as $tier => $cap)
        <div class="flex justify-between text-sm py-1"><span>Tier {{ $tier }}</span><span>{{ number_format($cap) }}</span></div>
        @endforeach
    </div>
</div>

<div class="dashboard-card p-5 mt-4">
    <h2 class="font-semibold mb-2">Generate</h2>
    <p class="text-xs text-slate-500 mb-3">Jalankan via CLI: <code class="font-mono">php artisan seo:generate --tier=1</code>, <code class="font-mono">--dry-run</code>, <code class="font-mono">--cluster=modules</code>, <code class="font-mono">--limit=50</code>. Audit: <code class="font-mono">php artisan seo:audit</code>. Sitemap: <code class="font-mono">php artisan seo:sitemap</code>.</p>
</div>
@endsection
