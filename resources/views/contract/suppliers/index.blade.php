@extends('layouts.app')

@section('title', ' - Kontrak Supplier')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Kontrak Supplier</h1>
        <p class="text-sm text-slate-500">Kontrak pengadaan barang / jasa</p>
    </div>
    @can('contract.create')
    <x-btn-create label="Buat Kontrak" :href="route('supplier-contracts.create')" />
    @endcan
</div>

<x-filter-bar :route="route('supplier-contracts.index')">
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'ACTIVE' => 'Aktif', 'COMPLETED' => 'Selesai', 'EXPIRED' => 'Kedaluwarsa', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Supplier</th>
        <th class="px-4 py-2.5">Item / Jasa</th>
        <th class="px-4 py-2.5 text-right">Nilai</th>
        <th class="px-4 py-2.5">Periode</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('supplier-contracts.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5">{{ $item->supplier?->name }}</td>
        <td class="px-4 py-2.5">{{ $item->item?->name ?? $item->service_description }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->contract_value ?? $item->price, 0) }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->start_date?->format('Y-m-d') }} → {{ $item->end_date?->format('Y-m-d') }}</td>
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
