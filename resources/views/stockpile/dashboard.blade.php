@extends('layouts.app')

@section('title', ' - Stockpile Board')

@section('content')
<x-ui.page-header title="Stockpile Board" description="Level & status survei semua pile.">
    <x-slot:actions>
        <x-ui.button variant="secondary" size="sm" :href="route('stockpiles.index')">Daftar Pile</x-ui.button>
        <x-ui.button variant="secondary" size="sm" :href="route('report.stockpile')">Rekonsiliasi</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-filter-bar :route="route('stockpiles.dashboard')">
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
</x-filter-bar>

<div class="grid md:grid-cols-3 gap-4">
    @forelse ($piles ?? [] as $p)
    <a href="{{ route('stockpiles.show', $p) }}" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-amber-400 block">
        <div class="flex items-center justify-between">
            <span class="font-mono font-bold text-slate-800">{{ $p->code }}</span>
            @if ($p->last_survey && $p->last_survey->status === 'INVESTIGATE')
                <x-status-badge status="PENDING" />
            @else
                <x-status-badge :status="$p->status ? 'ACTIVE' : 'INACTIVE'" />
            @endif
        </div>
        <div class="text-xs text-slate-500 mb-2">{{ $p->name }} · {{ $p->site?->name }}</div>
        <div class="h-2.5 rounded bg-slate-100 overflow-hidden"><div class="h-full {{ $p->fill_pct > 90 ? 'bg-red-500' : ($p->fill_pct > 70 ? 'bg-amber-500' : 'bg-green-500') }}" style="width: {{ min($p->fill_pct, 100) }}%"></div></div>
        <div class="mt-2 flex items-center justify-between text-sm">
            <span class="font-bold">{{ number_format($p->balance, 1) }} T</span>
            <span class="text-xs text-slate-500">{{ $p->fill_pct }}% dari {{ number_format($p->capacity_ton, 0) }} T</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1">Survei terakhir: {{ $p->last_survey?->survey_date ?? '—' }}</div>
    </a>
    @empty
    <div class="col-span-3 text-center text-slate-400 py-10">Belum ada stockpile aktif</div>
    @endforelse
</div>
@endsection
