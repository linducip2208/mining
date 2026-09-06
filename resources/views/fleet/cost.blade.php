@extends('layouts.app')

@section('title', ' - ' . ($title ?? 'Biaya Armada'))

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">{{ $title ?? 'Biaya Armada' }}</h1>
        <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }} · <a href="{{ route('fleet.dashboard') }}" class="text-indigo-600 hover:underline">Kembali ke dashboard</a></p>
    </div>
</div>

<x-filter-bar :route="url()->current()">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="grid md:grid-cols-3 gap-3 mb-4">
    <x-stat-card title="Total Biaya Armada" :value="'Rp ' . number_format($totals['cost'] ?? 0, 0)" color="amber" />
    <x-stat-card title="Jam Operasi" :value="number_format($totals['operating'] ?? 0, 1)" color="green" />
    <x-stat-card title="Rata-rata Rp/Jam" :value="'Rp ' . number_format(($totals['operating'] ?? 0) > 0 ? ($totals['cost'] ?? 0) / $totals['operating'] : 0, 0)" color="blue" />
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">BBM</th>
        <th class="px-4 py-2.5 text-right">Pemeliharaan</th>
        <th class="px-4 py-2.5 text-right">Penyusutan</th>
        <th class="px-4 py-2.5 text-right">Total</th>
        <th class="px-4 py-2.5 text-right">Rp/Jam Operasi</th>
    </x-slot:head>
    @forelse ($rows ?? [] as $r)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><span class="font-mono font-semibold">{{ $r['unit']->code }}</span> <span class="text-slate-500">{{ $r['unit']->name }}</span></td>
        <td class="px-4 py-2.5"><x-status-badge :status="$r['unit']->status" /></td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kpi']['fuel_cost'], 0) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kpi']['maintenance_cost'], 0) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kpi']['depreciation'], 0) }}</td>
        <td class="px-4 py-2.5 text-right font-semibold">Rp {{ number_format($r['kpi']['total_cost'], 0) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kpi']['cost_per_hour'], 0) }}</td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada unit pada filter ini</td></tr>
    @endforelse
</x-table>
@endsection
