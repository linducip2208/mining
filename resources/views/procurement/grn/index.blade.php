@extends('layouts.app')
@section('title', ' - Penerimaan Barang')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Goods Receipt (GRN)</h1>
    @can('goods_receipt.create')<x-btn-create :href="route('goods-receipts.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">PO</th>
        <th class="px-4 py-2.5">Supplier</th><th class="px-4 py-2.5">QC</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->receipt_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->purchaseOrder?->number }}</td>
            <td class="px-4 py-2.5">{{ $item->purchaseOrder?->supplier?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->qc_status }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'DRAFT')
                @can('goods_receipt.post')
                <form action="{{ route('goods-receipts.post', $item) }}" method="POST" class="inline" onsubmit="return confirm('Posting GRN? Stok akan bertambah.')">@csrf
                <button class="text-xs text-indigo-600 hover:underline">Posting</button></form>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada GRN</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection