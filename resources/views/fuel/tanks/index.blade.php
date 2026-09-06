@extends('layouts.app')

@section('title', ' - Tangki BBM')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Tangki BBM</h1>
        <p class="text-sm text-slate-500">Master tangki & depot bahan bakar</p>
    </div>
    @can('fuel.create')
    <x-btn-create label="Tambah Tangki" :href="route('fuel-tanks.create')" />
    @endcan
</div>

<x-filter-bar :route="route('fuel-tanks.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Perusahaan</th>
        <th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Kode</th>
        <th class="px-4 py-2.5">Nama</th>
        <th class="px-4 py-2.5">Jenis BBM</th>
        <th class="px-4 py-2.5 text-right">Kapasitas (L)</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->company?->name }}</td>
        <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->code }}</td>
        <td class="px-4 py-2.5">{{ $item->name }}</td>
        <td class="px-4 py-2.5">{{ $item->fuel_type }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->capacity_liter, 0) }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status ? 'ACTIVE' : 'INACTIVE'" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @can('fuel.update')<a href="{{ route('fuel-tanks.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
            @can('fuel.delete')
            <form action="{{ route('fuel-tanks.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
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
