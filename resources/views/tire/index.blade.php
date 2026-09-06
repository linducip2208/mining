@extends('layouts.app')

@section('title', ' - Ban')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Ban</h1>
        <p class="text-sm text-slate-500">Lifecycle ban: stok → pasang → rotasi → repair → scrap</p>
    </div>
    @can('tire.create')
    <x-btn-create label="Registrasi Ban" :href="route('tires.create')" />
    @endcan
</div>

<x-filter-bar :route="route('tires.index')">
    <x-filter-input name="q" label="Cari" placeholder="No seri..." />
    <x-filter-input name="status" label="Status" type="select" :options="['NEW' => 'Baru', 'STOCK' => 'Stok', 'INSTALLED' => 'Terpasang', 'REPAIR' => 'Repair', 'SCRAP' => 'Scrap']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">No Seri</th>
        <th class="px-4 py-2.5">Merek / Ukuran</th>
        <th class="px-4 py-2.5">Terpasang Di</th>
        <th class="px-4 py-2.5">Posisi</th>
        <th class="px-4 py-2.5 text-right">Biaya Beli</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('tires.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->serial_no }}</a></td>
        <td class="px-4 py-2.5">{{ $item->brand }} {{ $item->size }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->equipment?->code }}</td>
        <td class="px-4 py-2.5">{{ $item->position }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->purchase_cost, 0) }}</td>
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
