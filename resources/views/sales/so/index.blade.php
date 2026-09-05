@extends('layouts.app')
@section('title', ' - Order Penjualan')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Sales Order</h1>
    @can('sales_order.create')<x-btn-create :href="route('sales-orders.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Customer</th>
        <th class="px-4 py-2.5 text-right">Total</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5">Faktur</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->order_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->customer?->name }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-xs">
                @if ($item->invoice)
                    <a href="{{ route('invoices.show', $item->invoice) }}" class="text-amber-600 hover:underline">{{ $item->invoice->number }}</a>
                @else
                    <span class="text-slate-300">-</span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'SUBMITTED')
                @can('sales_order.approve')
                <form action="{{ route('sales-orders.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada SO</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection