@extends('layouts.app')

@section('title', ' - Laporan Inventory')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Inventory</h1>
    <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
    <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'inventory']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'inventory']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
</div>
<x-table>
    <thead slot="head">
        <th class="px-4 py-2.5">Kode</th><th class="px-4 py-2.5">Nama Item</th><th class="px-4 py-2.5">Gudang</th>
        <th class="px-4 py-2.5 text-right">Saldo</th><th class="px-4 py-2.5 text-right">Min. Stok</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Nilai Stok</th>
    </thead>
    <tbody>
        @forelse ($rows as $row)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $row->code }}</td>
            <td class="px-4 py-2.5">{{ $row->name }}</td>
            <td class="px-4 py-2.5">{{ $row->warehouse }}</td>
            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($row->balance, 2) }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($row->min_stock, 2) }}</td>
            <td class="px-4 py-2.5">
                @if ($row->min_stock > 0 && $row->balance < $row->min_stock)
                    <span class="text-xs font-bold text-red-600">KRITIS</span>
                @else
                    <span class="text-xs text-green-600">Aman</span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($row->cost_value, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada stok</td></tr>
        @endforelse
    </tbody>
</x-table>

@if ($critical->isNotEmpty())
<div class="bg-white rounded-xl border border-red-200 p-5 mt-4">
    <h3 class="font-semibold text-sm text-red-600 mb-3">Item Stok Kritis ({{ $critical->count() }})</h3>
    <div class="flex flex-wrap gap-2">
        @foreach ($critical as $row)
        <span class="px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">{{ $row->code }} · saldo {{ number_format($row->balance, 2) }} / min {{ number_format($row->min_stock, 2) }}</span>
        @endforeach
    </div>
</div>
@endif
@endsection
