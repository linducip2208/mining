@extends('layouts.app')

@section('title', ' - Kontrak Customer')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Kontrak Customer</h1>
        <p class="text-sm text-slate-500">Volume & harga jual mengikat — SO melebihi kontrak butuh override</p>
    </div>
    @can('contract.create')
    <x-btn-create label="Buat Kontrak" :href="route('customer-contracts.create')" />
    @endcan
</div>

<x-filter-bar :route="route('customer-contracts.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor..." />
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'ACTIVE' => 'Aktif', 'COMPLETED' => 'Selesai', 'EXPIRED' => 'Kedaluwarsa', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Customer</th>
        <th class="px-4 py-2.5">Produk</th>
        <th class="px-4 py-2.5 text-right">Volume (T)</th>
        <th class="px-4 py-2.5 text-right">Harga</th>
        <th class="px-4 py-2.5">Periode</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('customer-contracts.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5">{{ $item->customer?->name }}</td>
        <td class="px-4 py-2.5">{{ $item->item?->name }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->contract_qty, 1) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->price, 0) }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->start_date?->format('Y-m-d') }} → {{ $item->end_date?->format('Y-m-d') }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
