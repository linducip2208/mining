@extends('layouts.app')
@section('title', ' - Faktur')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Faktur Penjualan</h1>
    @can('invoice.create')<x-btn-create label="Dari SO" :href="route('invoices.create')" />@endcan
</div>
<x-filter-bar :route="route('invoices.index')">
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <input type="hidden" name="overdue" value="{{ request('overdue') ? 1 : '' }}">
    <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" name="overdue" value="1" @checked(request('overdue')) class="rounded text-amber-500"> Jatuh tempo</label>
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">SO</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Customer</th>
        <th class="px-4 py-2.5 text-right">Subtotal</th><th class="px-4 py-2.5 text-right">PPN</th><th class="px-4 py-2.5 text-right">Total</th>
        <th class="px-4 py-2.5 text-right">Dibayar</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $item->number }}</td>
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">
                @if ($item->salesOrder)
                    <a href="{{ route('sales-orders.show', $item->salesOrder) }}" class="text-amber-600 hover:underline">{{ $item->salesOrder->number }}</a>
                @else
                    <span class="text-slate-300">-</span>
                @endif
            </td>
            <td class="px-4 py-2.5 whitespace-nowrap">{{ $item->invoice_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->customer?->name }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">{{ number_format($item->tax_amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right font-semibold whitespace-nowrap">{{ number_format($item->total, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">{{ number_format($item->paid_amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5"><a href="{{ route('invoices.show', $item) }}" class="text-amber-600 text-xs hover:underline">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="10" class="px-4 py-10 text-center text-slate-400">Belum ada faktur</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection