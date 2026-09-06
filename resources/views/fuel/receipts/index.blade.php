@extends('layouts.app')

@section('title', ' - Penerimaan BBM')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Penerimaan BBM</h1>
        <p class="text-sm text-slate-500">BBM masuk ke tangki dari supplier / PO</p>
    </div>
    @can('fuel.create')
    <x-btn-create label="Buat Penerimaan" :href="route('fuel-receipts.create')" />
    @endcan
</div>

<x-filter-bar :route="route('fuel-receipts.index')">
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'POSTED' => 'Posted', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">Nomor</th>
        <th class="px-4 py-2.5">Tangki</th>
        <th class="px-4 py-2.5">Supplier</th>
        <th class="px-4 py-2.5 text-right">Liter</th>
        <th class="px-4 py-2.5 text-right">Harga/L</th>
        <th class="px-4 py-2.5 text-right">Total</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->receipt_date }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->number }}</td>
        <td class="px-4 py-2.5">{{ $item->tank?->code }}</td>
        <td class="px-4 py-2.5">{{ $item->supplier?->name }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($item->liter, 1) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->unit_price, 0) }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->total_cost, 0) }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right">
            @can('fuel.create')
            @if ($item->status === 'DRAFT')
            <form method="POST" action="{{ route('fuel-receipts.approve', $item) }}" class="inline">@csrf<button class="text-green-600 hover:underline text-xs">Ajukan Approval</button></form>
            @endif
            @endcan
            @can('fuel.post')
            @if ($item->status === 'APPROVED')
            <form method="POST" action="{{ route('fuel-receipts.post', $item) }}" onsubmit="return confirm('Posting penerimaan ini?')">
                @csrf<button class="text-amber-600 hover:underline text-xs">Posting</button>
            </form>
            @endif
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
