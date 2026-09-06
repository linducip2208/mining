@extends('layouts.app')

@section('title', ' - Dispatch Board')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Dispatch Board</h1>
        <p class="text-sm text-slate-500">Ritase harian {{ $date }} · <a href="{{ route('dispatch.trips.index') }}" class="text-indigo-600 hover:underline">Semua trip →</a></p>
    </div>
    @can('dispatch.assign')
    <x-btn-create label="Buat Trip" :href="route('dispatch.trips.create')" />
    @endcan
</div>

<x-filter-bar :route="route('dispatch.dashboard')">
    <x-filter-input name="date" label="Tanggal" type="date" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
    <x-filter-input name="shift_id" label="Shift" type="select" :options="$shifts ?? []" />
</x-filter-bar>

<div class="grid md:grid-cols-3 gap-3 mb-4">
    <x-stat-card title="Trip Aktif" :value="$summary['trips'] ?? 0" color="blue" />
    <x-stat-card title="Tonase (WB)" :value="number_format($summary['tonnage'] ?? 0, 1) . ' T'" color="green" />
    <x-stat-card title="Unit Jalan" :value="collect($summary['by_truck'] ?? [])->count()" color="amber" />
</div>

<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Trip Berjalan</div>
        <x-table>
            <x-slot:head>
                <th class="px-4 py-2.5">Trip</th>
                <th class="px-4 py-2.5">Truk</th>
                <th class="px-4 py-2.5">Muat → Bongkar</th>
                <th class="px-4 py-2.5">Status</th>
            </x-slot:head>
            @forelse ($active ?? [] as $t)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5"><a href="{{ route('dispatch.trips.show', $t) }}" class="font-mono text-indigo-600 hover:underline">{{ $t->number }}</a></td>
                <td class="px-4 py-2.5 font-mono">{{ $t->truck?->code }}</td>
                <td class="px-4 py-2.5 text-xs">{{ $t->loadingPoint?->code }} → {{ $t->dumpingPoint?->code }}</td>
                <td class="px-4 py-2.5"><x-status-badge :status="$t->status" /></td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Tidak ada trip berjalan</td></tr>
            @endforelse
        </x-table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-3">Tonase per Truk</div>
        <x-table>
            <x-slot:head>
                <th class="px-4 py-2.5">Truk</th>
                <th class="px-4 py-2.5 text-right">Rit</th>
                <th class="px-4 py-2.5 text-right">Tonase</th>
            </x-slot:head>
            @forelse ($summary['by_truck'] ?? [] as $row)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5 font-mono">{{ $row['truck'] ?? $row->truck ?? '—' }}</td>
                <td class="px-4 py-2.5 text-right">{{ $row['trips'] ?? $row->trips ?? 0 }}</td>
                <td class="px-4 py-2.5 text-right">{{ number_format($row['tonnage'] ?? $row->tonnage ?? 0, 1) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada ritase</td></tr>
            @endforelse
        </x-table>
    </div>
</div>
@endsection
