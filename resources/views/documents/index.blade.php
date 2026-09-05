@extends('layouts.app')
@section('title', ' - Dokumen')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Manajemen Dokumen</h1>
    @can('document.create')<x-btn-create :href="route('documents.create')" />@endcan
</div>
<x-filter-bar :route="route('documents.index')">
    <x-filter-input name="q" label="Cari" placeholder="Subjek / nomor..." />
    <x-filter-input name="category" label="Kategori" type="select" :options="$categories" />
    <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" name="expiring" value="1" @checked(request('expiring')) class="rounded text-amber-500"> Segera kadaluarsa</label>
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor Surat</th><th class="px-4 py-2.5">Subjek</th><th class="px-4 py-2.5">Kategori</th>
        <th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Kadaluarsa</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ Str::limit($item->subject, 50) }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $categories[$item->category] ?? $item->category }}</td>
            <td class="px-4 py-2.5">{{ $item->date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5 text-xs {{ $item->expiry_date && $item->expiry_date->isPast() ? 'text-red-600 font-semibold' : '' }}">{{ $item->expiry_date?->format('d/m/Y') ?? '-' }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                @if ($item->file_path)
                <a href="{{ route('documents.download', $item) }}" class="text-indigo-600 text-xs hover:underline">Unduh</a>
                @endif
                @if ($item->status === 'SUBMITTED')
                @can('document.approve')
                <form action="{{ route('documents.approve', $item) }}" method="POST" class="inline ml-1">@csrf
                <button class="text-green-600 text-xs hover:underline">Setujui</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada dokumen</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection