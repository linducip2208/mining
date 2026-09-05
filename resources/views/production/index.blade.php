@extends('layouts.app')
@section('title', ' - Produksi Crusher')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Batch Produksi</h1>
    @can('production.create')<x-btn-create label="Batch Baru" :href="route('production-batches.create')" />@endcan
</div>
<x-filter-bar :route="route('production-batches.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor batch..." />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Batch</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Crusher</th>
        <th class="px-4 py-2.5">Shift</th><th class="px-4 py-2.5 text-right">Input</th><th class="px-4 py-2.5 text-right">Gross</th>
        <th class="px-4 py-2.5 text-right">Loss</th><th class="px-4 py-2.5 text-right">Scrap</th><th class="px-4 py-2.5 text-right">Net</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->crusher?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->shift?->name }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->input_tonnage, 2) }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->gross_output, 2) }}</td>
            <td class="px-4 py-2.5 text-right text-orange-600">{{ number_format($item->total_loss, 2) }}</td>
            <td class="px-4 py-2.5 text-right text-red-500">{{ number_format($item->total_scrap, 2) }}</td>
            <td class="px-4 py-2.5 text-right font-bold text-green-700">{{ number_format($item->net_output, 2) }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5"><a href="{{ route('production-batches.show', $item) }}" class="text-amber-600 text-xs hover:underline">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="11" class="px-4 py-10 text-center text-slate-400">Belum ada batch</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection