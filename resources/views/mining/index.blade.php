@extends('layouts.app')
@section('title', ' - Aktivitas Tambang')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Aktivitas Tambang</h1>
    @can('mining.create')<x-btn-create label="Catat Aktivitas" :href="route('mining-activities.create')" />@endcan
</div>
<x-filter-bar :route="route('mining-activities.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor..." />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites" />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Pit</th><th class="px-4 py-2.5">Shift</th><th class="px-4 py-2.5">Peralatan</th>
        <th class="px-4 py-2.5">Operator</th><th class="px-4 py-2.5 text-right">Tonase</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->pit?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->shift?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->equipment?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->operator?->name }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->tonnage, 2) }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right"><a href="{{ route('mining-activities.show', $item) }}" class="text-amber-600 hover:underline text-xs">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="10" class="px-4 py-10 text-center text-slate-400">Belum ada aktivitas</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection