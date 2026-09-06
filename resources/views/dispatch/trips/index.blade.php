@extends('layouts.app')

@section('title', ' - Trip Dispatch')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Trip Dispatch</h1>
        <p class="text-sm text-slate-500">Siklus ritase: PLANNED → LOADING → HAULING → DUMPED → COMPLETED</p>
    </div>
    @can('dispatch.assign')
    <x-btn-create label="Buat Trip" :href="route('dispatch.trips.create')" />
    @endcan
</div>

<x-filter-bar :route="route('dispatch.trips.index')">
    <x-filter-input name="date" label="Tanggal" type="date" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
    <x-filter-input name="status" label="Status" type="select" :options="['PLANNED' => 'Planned', 'LOADING' => 'Loading', 'HAULING' => 'Hauling', 'DUMPED' => 'Dumped', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Truk / Sopir</th>
        <th class="px-4 py-2.5">Muat → Bongkar</th>
        <th class="px-4 py-2.5 text-right">Tonase</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->trip_date }}</td>
        <td class="px-4 py-2.5"><a href="{{ route('dispatch.trips.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5"><span class="font-mono">{{ $item->truck?->code }}</span> <span class="text-slate-500 text-xs">{{ $item->driver?->name }}</span></td>
        <td class="px-4 py-2.5 text-xs">{{ $item->loadingPoint?->code ?? '—' }} → {{ $item->dumpingPoint?->code ?? '—' }}</td>
        <td class="px-4 py-2.5 text-right">{{ $item->tonnage ? number_format($item->tonnage, 2) : '—' }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
