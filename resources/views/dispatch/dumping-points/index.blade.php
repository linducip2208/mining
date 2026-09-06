@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp

@section('title', ' - Titik Bongkar')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Titik Bongkar</h1>
        <p class="text-sm text-slate-500">Dumping point: stockpile, crusher, port, waste dump</p>
    </div>
    @can('dispatch.create')
    <x-btn-create label="Tambah Titik Bongkar" :href="route('dumping-points.create')" />
    @endcan
</div>

<x-filter-bar :route="route('dumping-points.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5">Kode</th>
        <th class="px-4 py-2.5">Nama</th>
        <th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5">Gudang</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->code }}</td>
        <td class="px-4 py-2.5">{{ $item->name }}</td>
        <td class="px-4 py-2.5">{{ HumanLabel::label($item->type) }}</td>
        <td class="px-4 py-2.5">{{ $item->warehouse?->name }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status ? 'ACTIVE' : 'INACTIVE'" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @can('dispatch.update')<a href="{{ route('dumping-points.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
            @can('dispatch.delete')
            <form action="{{ route('dumping-points.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
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
