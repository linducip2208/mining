@extends('layouts.app')
@section('title', ' - Master Sparepart')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Master Sparepart</h1>
    <a href="{{ route('sparepart.dashboard') }}" class="text-sm text-amber-600 hover:underline">← Dashboard Gudang</a>
</div>

<x-filter-bar :route="route('sparepart.master')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama / part number... (scanner friendly)" />
    <x-filter-input name="category_id" label="Kategori" type="select" :options="$categories->pluck('name', 'id')->all()" />
</x-filter-bar>

@can('sparepart.create')
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="font-semibold text-sm mb-3">Tambah Sparepart</h2>
    <form method="POST" action="{{ route('sparepart.master.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @csrf
        <input name="code" required maxlength="30" placeholder="Kode (SPR-001)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="name" required maxlength="150" placeholder="Nama sparepart" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] md:col-span-2">
        <select name="item_category_id" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <select name="unit_id" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
        <input name="brand" maxlength="100" placeholder="Brand" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="part_number" maxlength="100" placeholder="Part number" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="alt_part_number" maxlength="100" placeholder="Alt part number" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <select name="storage_location_id" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Lokasi --</option>@foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->warehouse?->code }} / {{ $l->code }}</option>@endforeach</select>
        <input name="min_stock" type="number" step="0.0001" min="0" placeholder="Min stock" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="max_stock" type="number" step="0.0001" min="0" placeholder="Max stock" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="reorder_point" type="number" step="0.0001" min="0" placeholder="Reorder point" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <select name="preferred_supplier_id" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Supplier --</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
        <input name="standard_cost" type="number" step="0.01" min="0" placeholder="Harga beli" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <div class="md:col-span-4"><button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Simpan</button></div>
    </form>
</div>
@endcan

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th><th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Kategori</th>
        <th class="px-4 py-2.5">Lokasi</th><th class="px-4 py-2.5 text-right">Min/ROP</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-mono font-medium whitespace-nowrap">{{ $item->code }}</td>
            <td class="px-4 py-2.5">{{ $item->name }}@if($item->brand) <span class="text-xs text-slate-400">· {{ $item->brand }}</span>@endif</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->category?->name }}</td>
            <td class="px-4 py-2.5 text-xs font-mono">{{ $item->storageLocation?->code ?? '-' }}</td>
            <td class="px-4 py-2.5 text-right text-xs whitespace-nowrap">{{ $item->min_stock }}/{{ $item->reorder_point }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap"><a href="{{ route('sparepart.card', ['item_id' => $item->id]) }}" class="text-amber-600 hover:underline text-xs">Kartu</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada sparepart</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
