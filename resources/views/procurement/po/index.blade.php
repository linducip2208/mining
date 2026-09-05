@extends('layouts.app')
@section('title', ' - Order Pembelian')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Purchase Order</h1>
    @can('purchase_order.create')<x-btn-create :href="route('purchase-orders.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Supplier</th>
        <th class="px-4 py-2.5 text-right">Subtotal</th><th class="px-4 py-2.5 text-right">Total</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->order_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->supplier?->name }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($item->total, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'SUBMITTED')
                @can('purchase_order.approve')
                <form action="{{ route('purchase-orders.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada PO</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection