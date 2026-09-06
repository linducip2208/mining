@extends('layouts.app')

@section('title', ' - Sampel QC')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Sampel QC</h1>
        <p class="text-sm text-slate-500">Hasil lab menentukan PASS / HOLD / REJECT otomatis</p>
    </div>
    @can('quality.create')
    <x-btn-create label="Buat Sampel" :href="route('samples.create')" />
    @endcan
</div>

<x-filter-bar :route="route('samples.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor..." />
    <x-filter-input name="status" label="Status" type="select" :options="['PENDING' => 'Pending', 'PASS' => 'Pass', 'HOLD' => 'Hold', 'REJECT' => 'Reject']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Sumber</th>
        <th class="px-4 py-2.5">Material</th>
        <th class="px-4 py-2.5">Customer</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->sample_date }}</td>
        <td class="px-4 py-2.5"><a href="{{ route('samples.show', $item) }}" class="font-mono text-indigo-600 hover:underline">{{ $item->number }}</a></td>
        <td class="px-4 py-2.5 text-xs">{{ $item->source_type }} #{{ $item->source_id }}</td>
        <td class="px-4 py-2.5">{{ $item->item?->name }}</td>
        <td class="px-4 py-2.5">{{ $item->customer?->name ?? '—' }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
