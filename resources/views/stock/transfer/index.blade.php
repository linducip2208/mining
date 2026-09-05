@extends('layouts.app')
@section('title', ' - Transfer Stok')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Transfer Stok</h1>
    @can('create')<x-btn-create :href="route('stock-transfers.create')" />@endcan
</div>
<x-filter-bar :route="route('stock-transfers.index')">
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Dari</th><th class="px-4 py-2.5">Ke</th><th class="px-4 py-2.5">Item</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->transfer_date?->format("d/m/Y") }}</td>
            <td class="px-4 py-2.5">{{ $item->fromWarehouse?->name }}</td>
            <td class="px-4 py-2.5">{{ $item->toWarehouse?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->items->map(fn($i) => $i->item?->name . " (" . number_format($i->qty, 2) . ")")->implode(", ") }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if (in_array($item->status, ["DRAFT", "APPROVED"]))
                @can("stock.post")
                <form action="{{ route("stock-transfers.post", $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-indigo-600 hover:underline">Posting</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection