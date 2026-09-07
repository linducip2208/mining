@extends('layouts.app')
@section('title', ' - Kartu Stok')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Kartu Stok</h1>
        <p class="text-sm text-slate-500">100% dari stock ledger</p>
    </div>
    <a href="{{ route('sparepart.dashboard') }}" class="text-sm text-amber-600 hover:underline">← Dashboard</a>
</div>

<x-filter-bar :route="route('sparepart.card')">
    <x-filter-input name="item_id" label="Sparepart" type="select" :options="$items->pluck('code', 'id')->all()" />
    <x-filter-input name="warehouse_id" label="Gudang" type="select" :options="$warehouses->pluck('name', 'id')->all()" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Referensi</th><th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5 text-right">Masuk</th><th class="px-4 py-2.5 text-right">Keluar</th><th class="px-4 py-2.5 text-right">Saldo</th>
        <th class="px-4 py-2.5 text-right">Nilai</th><th class="px-4 py-2.5">Gudang</th>
    </x-slot:head>
    <tbody>
        @forelse ($rows as $r)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $r['line']->trx_date }}</td>
            <td class="px-4 py-2.5 text-xs font-mono whitespace-nowrap">{{ $r['line']->ref_number }}</td>
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $r['line']->movement_type }}</td>
            <td class="px-4 py-2.5 text-right">{{ $r['line']->qty_in > 0 ? number_format($r['line']->qty_in, 2) : '-' }}</td>
            <td class="px-4 py-2.5 text-right">{{ $r['line']->qty_out > 0 ? number_format($r['line']->qty_out, 2) : '-' }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($r['balance'], 2) }}</td>
            <td class="px-4 py-2.5 text-right text-xs">{{ number_format($r['value'], 2) }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $r['line']->warehouse?->code }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Pilih sparepart untuk melihat kartu</td></tr>
        @endforelse
    </tbody>
</x-table>
@endsection
