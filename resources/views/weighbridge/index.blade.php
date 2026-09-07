@extends('layouts.app')
@section('title', ' - Timbangan')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Tiket Timbangan</h1>
    @can('weighbridge.create')<x-btn-create label="Timbang Pertama" :href="route('weighbridge-tickets.create')" />@endcan
</div>
<x-filter-bar :route="route('weighbridge-tickets.index')">
    <x-filter-input name="q" label="Cari" placeholder="No ticket / nopol..." />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tiket</th><th class="px-4 py-2.5">Waktu</th><th class="px-4 py-2.5">Nopol</th>
        <th class="px-4 py-2.5">Arah</th><th class="px-4 py-2.5">Customer/Supplier</th><th class="px-4 py-2.5">Item</th>
        <th class="px-4 py-2.5 text-right">Gross</th><th class="px-4 py-2.5 text-right">Tare</th><th class="px-4 py-2.5 text-right">Net (kg)</th>
        <th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $item->ticket_no }}</td>
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $item->first_weigh_at?->format('d/m H:i') }}{{ $item->second_weigh_at ? ' - ' . $item->second_weigh_at->format('H:i') : '' }}</td>
            <td class="px-4 py-2.5 whitespace-nowrap">{{ $item->vehicle_plate }}</td>
            <td class="px-4 py-2.5">{{ $item->direction === 'OUT' ? 'Keluar' : 'Masuk' }}</td>
            <td class="px-4 py-2.5">{{ $item->customer?->name ?? $item->supplier?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->item?->name }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->gross, 0) }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->tare, 0) }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($item->net, 0) }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right"><a href="{{ route('weighbridge-tickets.show', $item) }}" class="text-amber-600 hover:underline text-xs">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="11" class="px-4 py-10 text-center text-slate-400">Belum ada tiket</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection