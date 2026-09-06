@extends('layouts.app')

@section('title', ' - Stockpile')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Stockpile</h1>
        <p class="text-sm text-slate-500"><a href="{{ route('stockpiles.dashboard') }}" class="text-indigo-600 hover:underline">Board visual →</a></p>
    </div>
    @can('stockpile.create')
    <x-btn-create label="Tambah Stockpile" :href="route('stockpiles.create')" />
    @endcan
</div>

<x-filter-bar :route="route('stockpiles.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th>
        <th class="px-4 py-2.5">Nama</th>
        <th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Material</th>
        <th class="px-4 py-2.5 text-right">Saldo (T)</th>
        <th class="px-4 py-2.5 text-right">Kapasitas (T)</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('stockpiles.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->code }}</a></td>
        <td class="px-4 py-2.5">{{ $item->name }}</td>
        <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
        <td class="px-4 py-2.5">{{ $item->item?->name }}</td>
        <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($item->balance ?? 0, 2) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->capacity_ton, 1) }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status ? 'ACTIVE' : 'INACTIVE'" /></td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
