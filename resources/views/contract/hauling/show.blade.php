@extends('layouts.app')

@section('title', ' - Kontrak ' . $contract->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('hauling-contracts.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">Kontrak {{ $contract->number }} <x-status-badge :status="$contract->status" /></h1>
    <p class="text-sm text-slate-500">{{ $contract->supplier?->name }} · {{ $contract->route?->name ?? 'Semua rute' }} · {{ $contract->start_date?->format('Y-m-d') }} → {{ $contract->end_date?->format('Y-m-d') }}</p>
</div>

<div class="grid md:grid-cols-5 gap-3 mb-4">
    <x-stat-card title="Trip" :value="$real['trips'] ?? 0" color="slate" />
    <x-stat-card title="Tonase" :value="number_format($real['tons'] ?? 0, 1) . ' T'" color="blue" />
    <x-stat-card title="Ton-Km" :value="number_format($real['ton_km'] ?? 0, 1)" color="indigo" />
    <x-stat-card title="Nilai (tarif {{ $contract->rate_type }})" :value="'Rp ' . number_format($real['cost'] ?? 0, 0)" color="green" />
    <x-stat-card title="Shortfall Min. Volume" :value="$real['shortfall'] !== null ? number_format($real['shortfall'], 1) . ' T' : '—'" color="{{ ($real['shortfall'] ?? 0) > 0 ? 'red' : 'amber' }}" />
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="grid md:grid-cols-4 gap-3 text-sm">
        <div><div class="text-[11px] uppercase text-slate-500">Tipe Tarif</div><div class="font-semibold">{{ $contract->rate_type }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Tarif</div><div class="font-bold">Rp {{ number_format($contract->rate, 0) }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Volume Minimum</div><div class="font-semibold">{{ $contract->minimum_volume ? number_format($contract->minimum_volume, 1) . ' T' : '—' }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Jarak Rute</div><div class="font-semibold">{{ $contract->route?->distance_km ? $contract->route->distance_km . ' km' : '—' }}</div></div>
    </div>
    @can('contract.create')
    @if ($contract->status === 'DRAFT')
    <form method="POST" action="{{ route('hauling-contracts.approve', $contract) }}" class="mt-4" onsubmit="return confirm('Aktifkan kontrak ini?')">
        @csrf<button class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">Ajukan Approval</button>
    </form>
    @endif
    @endcan
</div>
@endsection
