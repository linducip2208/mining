@extends('layouts.app')
@section('title', ' - Surat Jalan')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Delivery Order</h1>
    @can('delivery_order.create')<x-btn-create :href="route('delivery-orders.create', request()->only('sales_order_id'))" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">SO</th>
        <th class="px-4 py-2.5">Customer</th><th class="px-4 py-2.5">Nopol</th><th class="px-4 py-2.5 text-right">Qty</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->delivery_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->salesOrder?->number }}</td>
            <td class="px-4 py-2.5">{{ $item->salesOrder?->customer?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->vehicle_plate }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->total_qty, 2) }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'DRAFT')
                <form action="{{ route('delivery-orders.complete', $item) }}" method="POST" class="inline-flex gap-1 items-center" onsubmit="const id=prompt('ID Tiket Timbangan (lihat di modul timbangan):'); if(id){this.wbt.value=id}else{return false}">
                    @csrf <input type="hidden" name="weighbridge_ticket_id" id="wbt">
                    <button class="text-xs text-green-600 hover:underline">Selesaikan + Timbang</button></form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada DO</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection