@extends('layouts.app')
@section('title', ' - SEO Page')
@section('content')
<x-ui.page-header :title="$page->keyword" :description="'/' . $page->path . ' · ' . $page->intent . ' · quality ' . $page->quality_score . '/' . $page->uniqueness_score">
    <x-slot:actions>
        <x-ui.button size="sm" variant="secondary" :href="route('marketing.seo.pages')">Kembali</x-ui.button>
        <a href="{{ $page->url() }}" target="_blank" class="inline-flex min-h-[38px] items-center px-3.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">Preview</a>
    </x-slot:actions>
</x-ui.page-header>

@if($page->noindex_reason)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4 text-sm text-amber-800">Noindex: {{ $page->noindex_reason }}</div>
@endif

<div class="dashboard-card p-5 mb-4">
    <h1 class="text-xl font-bold">{{ $page->h1 }}</h1>
    <p class="text-sm text-slate-500 mt-1">{{ $page->title }}</p>
    <p class="text-sm text-slate-500 mt-1">{{ $page->description }}</p>
    <p class="text-xs text-slate-400 mt-2 font-mono">canonical: {{ $page->canonical }} · v{{ $page->content_version }} · hash {{ substr($page->content_hash ?? '-', 0, 12) }}</p>
</div>

<div class="flex flex-wrap gap-2 mb-4">
    <form action="{{ route('marketing.seo.publish', $page) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold min-h-[38px]">Publish</button></form>
    <form action="{{ route('marketing.seo.noindex', $page) }}" method="POST" class="flex gap-2">@csrf<input name="reason" placeholder="Alasan noindex" class="px-3 py-2 rounded-lg border border-slate-200 text-sm"><button class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm min-h-[38px]">Noindex</button></form>
    <form action="{{ route('marketing.seo.archive', $page) }}" method="POST" class="flex gap-2">@csrf<input name="redirect_to" placeholder="Redirect ke (opsional)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm"><button class="px-4 py-2 rounded-lg bg-slate-100 text-sm min-h-[38px]">Archive</button></form>
</div>

<div class="dashboard-card p-5">
    <h2 class="font-semibold mb-2">Related ({{ count($related) }})</h2>
    <ul class="text-sm space-y-1">
        @forelse($related as $r)<li><a href="{{ $r['url'] }}" class="text-amber-600 hover:underline">{{ $r['title'] }}</a> <span class="text-xs text-slate-400">({{ $r['label'] }})</span></li>
        @empty<li class="text-slate-400">Tidak ada related — calon orphan.</li>@endforelse
    </ul>
</div>
@endsection
