@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp
@section('title', ' - Kartu Stok')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Kartu Stok</h1>
<x-filter-bar :route="route('stock.card')">
    <x-filter-input name="item_id" label="Item" type="select" :options="$items->pluck('name','id')->all()" placeholder="Pilih item..." />
    <x-filter-input name="warehouse_id" label="Gudang" type="select" :options="$warehouses" />
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
@if ($item)
<div class="bg-white rounded-xl border border-slate-200 mb-4 p-4 flex justify-between items-center">
    <div><span class="text-xs text-slate-400">Item</span><div class="font-semibold">{{ $item->code }} - {{ $item->name }}</div></div>
    <div class="text-right"><span class="text-xs text-slate-400">Saldo Akhir</span>
    <div class="text-xl font-bold text-amber-600">{{ number_format($rows->last()?->running ?? 0, 2) }}</div></div>
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Tipe</th><th class="px-4 py-2.5">Ref</th>
        <th class="px-4 py-2.5 text-right">Masuk</th><th class="px-4 py-2.5 text-right">Keluar</th><th class="px-4 py-2.5 text-right">Saldo</th>
    </x-slot:head>
    <tbody>
        @foreach ($rows as $row)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $row->trx_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5 text-xs">{{ HumanLabel::label($row->movement_type) }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $row->ref_number ?? '-' }}</td>
            <td class="px-4 py-2.5 text-right text-green-600">{{ $row->qty_in > 0 ? number_format($row->qty_in, 2) : '' }}</td>
            <td class="px-4 py-2.5 text-right text-red-600">{{ $row->qty_out > 0 ? number_format($row->qty_out, 2) : '' }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($row->running, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</x-table>
@else
<div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">Pilih item untuk melihat kartu stok</div>
@endif
@endsection
