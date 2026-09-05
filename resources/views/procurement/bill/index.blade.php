@extends('layouts.app')
@section('title', ' - Tagihan Vendor')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Tagihan Vendor (AP)</h1>
    @can('vendor_bill.create')<x-btn-create :href="route('vendor-bills.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">No Invoice Supplier</th><th class="px-4 py-2.5">Supplier</th><th class="px-4 py-2.5">PO</th>
        <th class="px-4 py-2.5 text-right">Total</th><th class="px-4 py-2.5 text-right">Dibayar</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->supplier_invoice_no }}</td>
            <td class="px-4 py-2.5">{{ $item->supplier?->name }}</td>
            <td class="px-4 py-2.5 text-xs">
                @if ($item->purchaseOrder)
                    <a href="{{ route('purchase-orders.show', $item->purchaseOrder) }}" class="text-amber-600 hover:underline">{{ $item->purchaseOrder->number }}</a>
                @else
                    <span class="text-slate-300">-</span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->total, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->paid_amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'DRAFT')
                @can('vendor_bill.post')
                <form action="{{ route('vendor-bills.post', $item) }}" method="POST" class="inline" onsubmit="return confirm('Posting tagihan? Jurnal AP akan dibuat.')">@csrf
                <button class="text-xs text-indigo-600 hover:underline">Posting</button></form>
                @endcan
                @elseif (in_array($item->status, ['POSTED', 'PARTIALLY_PAID']))
                @can('vendor_bill.post')
                <form action="{{ route('vendor-bills.pay', $item) }}" method="POST" class="inline-flex gap-1" onsubmit="const a=prompt('Jumlah bayar:'); if(a){this.amount.value=a}else{return false}">
                    @csrf <input type="hidden" name="amount"><input type="hidden" name="payment_date" value="{{ today()->toDateString() }}">
                    <button class="text-xs text-green-600 hover:underline">Bayar</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada tagihan</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection