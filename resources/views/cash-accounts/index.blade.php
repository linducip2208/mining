@extends('layouts.app')

@section('title', ' - Kas & Bank')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Kas & Bank</h1>
        <p class="text-sm text-slate-500">Kelola data Kas & Bank</p>
    </div>
    @can('cash-accounts.create')
    <x-btn-create label="Tambah Kas & Bank" :href="route('cash-accounts.create')" />
    @endcan
</div>

<x-filter-bar :route="route('cash-accounts.index')">
    <x-filter-input name="q" label="Cari" placeholder="Kode / nama..." />
</x-filter-bar>

<x-table>
    <x-slot:head>
                        <th class="px-4 py-2.5">Perusahaan</th>
                        <th class="px-4 py-2.5">Kode</th>
                        <th class="px-4 py-2.5">Nama</th>
                        <th class="px-4 py-2.5">Tipe</th>
                        <th class="px-4 py-2.5">Nama Bank</th>
                        <th class="px-4 py-2.5">No Rekening</th>
                        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">{{ $item->company?->name }}</td>
                        <td class="px-4 py-2.5">{{ $item->code }}</td>
                        <td class="px-4 py-2.5">{{ $item->name }}</td>
                        <td class="px-4 py-2.5">{{ $item->type }}</td>
                        <td class="px-4 py-2.5">{{ $item->bank_name }}</td>
                        <td class="px-4 py-2.5">{{ $item->account_no }}</td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @can('cash-accounts.update')<a href="{{ route('cash-accounts.edit', $item) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>@endcan
                            @can('cash-accounts.delete')
                            <form action="{{ route('cash-accounts.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
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