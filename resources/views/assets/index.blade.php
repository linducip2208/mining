@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp

@section('title', ' - Aset')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Aset</h1>
        <p class="text-sm text-slate-500">Kelola data Aset</p>
    </div>
    @can('assets.create')
    <x-btn-create label="Tambah Aset" :href="route('assets.create')" />
    @endcan
</div>

<x-filter-bar :route="route('assets.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
                        <th class="px-4 py-2.5">Perusahaan</th>
                        <th class="px-4 py-2.5">Site</th>
                        <th class="px-4 py-2.5">Kode</th>
                        <th class="px-4 py-2.5">Nama</th>
                        <th class="px-4 py-2.5">Tipe</th>
                        <th class="px-4 py-2.5">No Seri</th>
                        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">{{ $item->company?->name }}</td>
                        <td class="px-4 py-2.5">{{ $item->site?->name }}</td>
                        <td class="px-4 py-2.5">{{ $item->code }}</td>
                        <td class="px-4 py-2.5">{{ $item->name }}</td>
                        <td class="px-4 py-2.5">{{ HumanLabel::label($item->type) }}</td>
                        <td class="px-4 py-2.5">{{ $item->serial_no }}</td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @can('assets.update')<a href="{{ route('assets.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
                            @can('assets.delete')
                            <form action="{{ route('assets.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline text-xs ml-2">Hapus</button>
                            </form>
                            @endcan
                        </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
