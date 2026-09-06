@extends('layouts.app')

@section('title', ' - Kontrak Hauling')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Kontrak Hauling</h1>
        <p class="text-sm text-slate-500">Tarif angkutan per ton / km / trip</p>
    </div>
    @can('contract.create')
    <x-btn-create label="Buat Kontrak" :href="route('hauling-contracts.create')" />
    @endcan
</div>

<x-filter-bar :route="route('hauling-contracts.index')">
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'ACTIVE' => 'Aktif', 'COMPLETED' => 'Selesai', 'EXPIRED' => 'Kedaluwarsa', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Kontraktor</th>
        <th class="px-4 py-2.5">Rute</th>
        <th class="px-4 py-2.5">Tipe Tarif</th>
        <th class="px-4 py-2.5 text-right">Tarif</th>
        <th class="px-4 py-2.5 text-right">Min. Volume</th>
        <th class="px-4 py-2.5">Periode</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('hauling-contracts.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5">{{ $item->supplier?->name }}</td>
        <td class="px-4 py-2.5">{{ $item->route?->name ?? '—' }}</td>
        <td class="px-4 py-2.5">{{ $item->rate_type }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->rate, 0) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $item->minimum_volume ? number_format($item->minimum_volume, 1) : '—' }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->start_date?->format('Y-m-d') }} → {{ $item->end_date?->format('Y-m-d') }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right">
            @can('contract.approve')
            @if ($item->status === 'DRAFT')
            <form method="POST" action="{{ route('hauling-contracts.approve', $item) }}" class="inline">@csrf<button class="text-green-600 hover:underline text-xs">Aktifkan</button></form>
            @endif
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
