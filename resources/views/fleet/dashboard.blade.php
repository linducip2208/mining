@extends('layouts.app')

@section('title', ' - Dashboard Armada')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Dashboard Armada</h1>
        <p class="text-sm text-slate-500">Ketersediaan, utilisasi & biaya unit {{ $from }} s.d. {{ $to }}</p>
    </div>
    <div class="flex gap-2 text-xs">
        <a href="{{ route('fleet.availability') }}" class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Ketersediaan</a>
        <a href="{{ route('fleet.utilization') }}" class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Utilisasi</a>
        <a href="{{ route('fleet.downtime') }}" class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Downtime</a>
        <a href="{{ route('fleet.cost') }}" class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Biaya</a>
    </div>
</div>

<x-filter-bar :route="route('fleet.dashboard')">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="grid md:grid-cols-5 gap-3 mb-4">
    <x-stat-card title="Unit" :value="$totals['units'] ?? 0" color="slate" />
    <x-stat-card title="Jam Operasi" :value="number_format($totals['operating'] ?? 0, 1)" color="green" />
    <x-stat-card title="Downtime (Jam)" :value="number_format($totals['downtime'] ?? 0, 1)" color="red" />
    <x-stat-card title="PA %" :value="($totals['pa_pct'] ?? 0) . '%'" color="blue" />
    <x-stat-card title="Total Biaya" :value="'Rp ' . number_format($totals['cost'] ?? 0, 0)" color="amber" />
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Komposisi Status Unit</div>
    <div class="flex flex-wrap gap-2">
        @forelse ($byStatus ?? [] as $st => $n)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-xs font-semibold"><x-status-badge :status="$st" /> {{ $n }} unit</span>
        @empty
            <span class="text-sm text-slate-400">Belum ada unit.</span>
        @endforelse
    </div>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Operasi (H)</th>
        <th class="px-4 py-2.5 text-right">Idle (H)</th>
        <th class="px-4 py-2.5 text-right">Downtime (H)</th>
        <th class="px-4 py-2.5 text-right">PA %</th>
        <th class="px-4 py-2.5 text-right">Util %</th>
        <th class="px-4 py-2.5 text-right">Biaya</th>
        <th class="px-4 py-2.5 text-right">Rp/Jam</th>
    </x-slot:head>
    @forelse ($rows ?? [] as $r)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><span class="font-mono font-semibold">{{ $r['unit']->code }}</span> <span class="text-slate-500">{{ $r['unit']->name }}</span></td>
        <td class="px-4 py-2.5"><x-status-badge :status="$r['unit']->status" /></td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['operating_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['idle_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['downtime_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $r['kpi']['pa_pct'] }}%</td>
        <td class="px-4 py-2.5 text-right">{{ $r['kpi']['utilization_pct'] }}%</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kpi']['total_cost'], 0) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kpi']['cost_per_hour'], 0) }}</td>
    </tr>
    @empty
    <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada unit pada filter ini</td></tr>
    @endforelse
</x-table>
@endsection
