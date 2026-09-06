@extends('layouts.app')

@section('title', ' - Laporan Penjualan')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Penjualan</h1>
    <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
    <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'sales']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'sales']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
</div>
<x-filter-bar :route="route('report.sales')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Penjualan per Customer</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b">
            <th class="py-2">Customer</th><th class="py-2 text-right">Faktur</th><th class="py-2 text-right">Total</th><th class="py-2 text-right">Dibayar</th><th class="py-2 text-right">Outstanding</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($perCustomer as $row)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ $row->name }}</td>
                <td class="py-1.5 text-right">{{ $row->invoices }}</td>
                <td class="py-1.5 text-right">Rp {{ number_format($row->total, 0, ',', '.') }}</td>
                <td class="py-1.5 text-right text-green-700">Rp {{ number_format($row->paid, 0, ',', '.') }}</td>
                <td class="py-1.5 text-right font-semibold {{ ($row->total - $row->paid) > 0 ? 'text-red-600' : 'text-slate-400' }}">Rp {{ number_format($row->total - $row->paid, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="py-6 text-center text-slate-400">Tidak ada penjualan pada periode ini</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Volume per Produk</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b">
            <th class="py-2">Produk</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Nilai</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($perItem as $row)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ $row->name }}</td>
                <td class="py-1.5 text-right">{{ number_format($row->qty, 2) }}</td>
                <td class="py-1.5 text-right font-medium">Rp {{ number_format($row->total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="py-6 text-center text-slate-400">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
