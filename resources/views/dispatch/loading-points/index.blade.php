@extends('layouts.app')

@section('title', ' - Titik Muat')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Titik Muat</h1>
        <p class="text-sm text-slate-500">Loading point per pit / front</p>
    </div>
    @can('dispatch.create')
    <x-btn-create label="Tambah Titik Muat" :href="route('loading-points.create')" />
    @endcan
</div>

<x-filter-bar :route="route('loading-points.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Pit</th>
        <th class="px-4 py-2.5">Kode</th>
        <th class="px-4 py-2.5">Nama</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
        <td class="px-4 py-2.5">{{ $item->pit?->name }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->code }}</td>
        <td class="px-4 py-2.5">{{ $item->name }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status ? 'ACTIVE' : 'INACTIVE'" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @can('dispatch.update')<a href="{{ route('loading-points.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
            @can('dispatch.delete')
            <form action="{{ route('loading-points.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline text-xs ml-2">Hapus</button>
            </form>
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
