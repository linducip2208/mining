@extends('layouts.app')

@section('title', ' - Spesifikasi Produk')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Spesifikasi Produk</h1>
        <p class="text-sm text-slate-500">Batas min/max per parameter — acuan verdict lab otomatis</p>
    </div>
    @can('quality.create')
    <x-btn-create label="Buat Spesifikasi" :href="route('specs.create')" />
    @endcan
</div>

@if (isset($spec))
<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-4 text-sm">
    <div class="font-bold text-indigo-800">{{ $spec->item?->name }} — {{ $spec->customer?->name ?? 'Umum' }} ({{ $spec->effective_date }}) <x-status-badge :status="$spec->status" /></div>
    <div class="mt-2 grid md:grid-cols-4 gap-2">
        @foreach ($spec->lines ?? [] as $l)
        <div class="bg-white rounded-lg border border-indigo-100 px-3 py-2">
            <div class="font-semibold">{{ $l->parameter?->code }}</div>
            <div class="text-xs text-slate-500">Min {{ $l->min_value ?? '—' }} · Max {{ $l->max_value ?? '—' }} · Target {{ $l->target_value ?? '—' }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

<x-filter-bar :route="route('specs.index')">
    <x-filter-input name="status" label="Status" type="select" :options="['ACTIVE' => 'Aktif', 'EXPIRED' => 'Kedaluwarsa', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Produk</th>
        <th class="px-4 py-2.5">Customer</th>
        <th class="px-4 py-2.5">Berlaku</th>
        <th class="px-4 py-2.5">Kedaluwarsa</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><a href="{{ route('specs.show', $item) }}" class="text-indigo-600 hover:underline">{{ $item->item?->name }}</a></td>
        <td class="px-4 py-2.5">{{ $item->customer?->name ?? 'Umum' }}</td>
        <td class="px-4 py-2.5">{{ $item->effective_date }}</td>
        <td class="px-4 py-2.5">{{ $item->expiry_date ?? '—' }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
    </tr>
    @empty
    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
