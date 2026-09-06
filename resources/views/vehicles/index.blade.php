@extends('layouts.app')
@php use App\Support\HumanLabel; use App\Support\StatusLabel; @endphp

@section('title', ' - Kendaraan')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Kendaraan</h1>
        <p class="text-sm text-slate-500">Kendaraan ringan / operasional (LV, bus, ambulance)</p>
    </div>
    @can('fleet.create')
    <x-btn-create label="Tambah Kendaraan" :href="route('vehicles.create')" />
    @endcan
</div>

<x-filter-bar :route="route('vehicles.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / plat / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Perusahaan</th>
        <th class="px-4 py-2.5">Kode</th>
        <th class="px-4 py-2.5">Plat</th>
        <th class="px-4 py-2.5">Nama</th>
        <th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5 text-right">Kapasitas (Ton)</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->company?->name }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->code }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->plate_no }}</td>
        <td class="px-4 py-2.5">{{ $item->name }}</td>
        <td class="px-4 py-2.5">{{ HumanLabel::label($item->type) }}</td>
        <td class="px-4 py-2.5 text-right">{{ $item->capacity_ton }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @can('fleet.update')<a href="{{ route('vehicles.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
            @can('fleet.delete')
            <form action="{{ route('vehicles.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline text-xs ml-2">Hapus</button>
            </form>
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
