@extends('layouts.app')

@section('title', ' - Dashboard BBM')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Dashboard BBM</h1>
        <p class="text-sm text-slate-500">Stok tangki & konsumsi {{ $from }} s.d. {{ $to }}</p>
    </div>
    <div class="flex gap-2 text-xs">
        <a href="{{ route('fuel.stock') }}" class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Stok</a>
        <a href="{{ route('fuel.consumption') }}" class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Konsumsi</a>
        <a href="{{ route('fuel.variance') }}" class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Anomali</a>
    </div>
</div>

<x-filter-bar :route="route('fuel.dashboard')">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="grid md:grid-cols-4 gap-3 mb-4">
    <x-stat-card title="Total Konsumsi" :value="number_format($totalLiter ?? 0, 0) . ' L'" color="blue" />
    <x-stat-card title="Nilai Konsumsi" :value="'Rp ' . number_format($totalCost ?? 0, 0)" color="amber" />
    <x-stat-card title="Tangki Aktif" :value="count($tanks ?? [])" color="slate" />
    <x-stat-card title="Stok Tangki" :value="number_format(collect($tanks ?? [])->sum('balance'), 0) . ' L'" color="green" />
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="text-sm font-bold text-slate-700 mb-3">Level Tangki</div>
    <div class="grid md:grid-cols-3 gap-3">
        @forelse ($tanks ?? [] as $t)
        <div class="rounded-lg border border-slate-200 p-3">
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold">{{ $t->code }}</span>
                <span class="text-xs text-slate-500">{{ $t->fill_pct }}%</span>
            </div>
            <div class="text-xs text-slate-500 mb-2">{{ $t->name }} · {{ $t->site?->name }}</div>
            <div class="h-2 rounded bg-slate-100 overflow-hidden"><div class="h-full {{ $t->fill_pct < 20 ? 'bg-red-500' : ($t->fill_pct < 50 ? 'bg-amber-500' : 'bg-green-500') }}" style="width: {{ min($t->fill_pct, 100) }}%"></div></div>
            <div class="mt-1.5 text-xs text-slate-600">{{ number_format($t->balance, 0) }} L · @Rp {{ number_format($t->avg_cost, 0) }}/L</div>
        </div>
        @empty
        <span class="text-sm text-slate-400">Belum ada tangki aktif.</span>
        @endforelse
    </div>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5 text-right">Liter</th>
        <th class="px-4 py-2.5 text-right">L/H</th>
        <th class="px-4 py-2.5">Variansi</th>
    </x-slot:head>
    @forelse (collect($issues ?? [])->take(20) as $i)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $i->issue_date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $i->number }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $i->equipment?->code ?? $i->vehicle_plate }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($i->liter, 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $i->liter_per_hour }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="($i->variance_status ?? 'NORMAL') === 'NORMAL' ? 'VALIDATED' : (($i->variance_status ?? '') === 'CRITICAL' ? 'BREAKDOWN' : 'PENDING')" /></td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada konsumsi periode ini</td></tr>
    @endforelse
</x-table>
@endsection
