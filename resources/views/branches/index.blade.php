@extends('layouts.app')

@section('title', ' - Cabang')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Cabang</h1>
        <p class="text-sm text-slate-500">Kelola data Cabang</p>
    </div>
    @can('branches.create')
    <x-btn-create label="Tambah Cabang" :href="route('branches.create')" />
    @endcan
</div>

<x-filter-bar :route="route('branches.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
                        <th class="px-4 py-2.5">Perusahaan</th>
                        <th class="px-4 py-2.5">Kode</th>
                        <th class="px-4 py-2.5">Nama</th>
                        <th class="px-4 py-2.5">Kota</th>
                        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">{{ $item->company?->name }}</td>
                        <td class="px-4 py-2.5">{{ $item->code }}</td>
                        <td class="px-4 py-2.5">{{ $item->name }}</td>
                        <td class="px-4 py-2.5">{{ $item->city }}</td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @can('branches.update')<a href="{{ route('branches.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
                            @can('branches.delete')
                            <form action="{{ route('branches.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
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