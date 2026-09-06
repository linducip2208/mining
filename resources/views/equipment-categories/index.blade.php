@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp

@section('title', ' - Kategori Alat')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Kategori Alat</h1>
        <p class="text-sm text-slate-500">Master kategori unit: standar konsumsi BBM & threshold anomali</p>
    </div>
    @can('fleet.create')
    <x-btn-create label="Tambah Kategori" :href="route('equipment-categories.create')" />
    @endcan
</div>

<x-filter-bar :route="route('equipment-categories.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th>
        <th class="px-4 py-2.5">Nama</th>
        <th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5 text-right">Std L/H</th>
        <th class="px-4 py-2.5 text-right">Warning %</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5 font-mono">{{ $item->code }}</td>
        <td class="px-4 py-2.5">{{ $item->name }}</td>
        <td class="px-4 py-2.5">{{ HumanLabel::label($item->type) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $item->standard_fuel_lph }}</td>
        <td class="px-4 py-2.5 text-right">{{ $item->fuel_warning_pct }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status ? 'ACTIVE' : 'INACTIVE'" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @can('fleet.update')<a href="{{ route('equipment-categories.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
            @can('fleet.delete')
            <form action="{{ route('equipment-categories.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline text-xs ml-2">Hapus</button>
            </form>
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
