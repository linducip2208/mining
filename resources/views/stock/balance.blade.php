@extends('layouts.app')
@section('title', ' - Saldo Stok')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Saldo Stok (Stock Ledger)</h1>
<x-filter-bar :route="route('stock.balance')">
    <x-filter-input name="q" label="Cari" placeholder="Nama item..." />
    <x-filter-input name="warehouse_id" label="Gudang" type="select" :options="$warehouses" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th><th class="px-4 py-2.5">Nama Item</th><th class="px-4 py-2.5">Gudang</th>
        <th class="px-4 py-2.5 text-right">Saldo</th><th class="px-4 py-2.5 text-right">Nilai (Rp)</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($rows as $row)
        <tr class="hover:bg-slate-50 {{ $row->balance < 0 ? 'bg-red-50' : '' }}">
            <td class="px-4 py-2.5">{{ $row->code }}</td>
            <td class="px-4 py-2.5">{{ $row->name }}</td>
            <td class="px-4 py-2.5">{{ $row->warehouse }}</td>
            <td class="px-4 py-2.5 text-right font-semibold {{ $row->balance < 0 ? 'text-red-600' : '' }}">{{ number_format($row->balance, 2) }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($row->value, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><a href="{{ route('stock.card', ['item_id' => $row->item_id]) }}" class="text-amber-600 text-xs hover:underline">Kartu Stok</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Tidak ada saldo stok</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $rows->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection