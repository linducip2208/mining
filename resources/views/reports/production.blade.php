@extends('layouts.app')

@section('title', ' - Laporan Produksi')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Produksi Crusher</h1>
    <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
    <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'production']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'production']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
</div>
<x-filter-bar :route="route('report.production')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Batch Produksi</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b">
            <th class="py-2">Batch</th><th class="py-2">Tanggal</th><th class="py-2">Crusher</th>
            <th class="py-2 text-right">Input</th><th class="py-2 text-right">Gross</th><th class="py-2 text-right">Loss</th>
            <th class="py-2 text-right">Scrap</th><th class="py-2 text-right">Net</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($batches as $b)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ $b->number }}</td>
                <td class="py-1.5">{{ $b->date?->format('d/m/Y') }}</td>
                <td class="py-1.5">{{ $b->crusher?->name }}</td>
                <td class="py-1.5 text-right">{{ number_format($b->input_tonnage, 2) }}</td>
                <td class="py-1.5 text-right">{{ number_format($b->gross_output, 2) }}</td>
                <td class="py-1.5 text-right text-orange-600">{{ number_format($b->total_loss, 2) }}</td>
                <td class="py-1.5 text-right text-red-500">{{ number_format($b->total_scrap, 2) }}</td>
                <td class="py-1.5 text-right font-bold text-green-700">{{ number_format($b->net_output, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="py-6 text-center text-slate-400">Tidak ada batch pada periode ini</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-slate-300 font-bold">
                <td colspan="4" class="py-2">TOTAL</td>
                <td class="py-2 text-right">{{ number_format($batches->sum('input_tonnage'), 2) }}</td>
                <td class="py-2 text-right">{{ number_format($batches->sum('gross_output'), 2) }}</td>
                <td class="py-2 text-right">{{ number_format($batches->sum('total_loss'), 2) }}</td>
                <td class="py-2 text-right text-green-700">{{ number_format($batches->sum('net_output'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold text-sm mb-3">Rekap Loss per Kategori</h3>
    <table class="w-full text-sm">
        <tbody class="divide-y divide-slate-100">
            @forelse ($lossSummary as $cat => $ton)
            <tr><td class="py-1.5">{{ $cat }}</td><td class="py-1.5 text-right font-medium">{{ number_format($ton, 2) }} Ton</td></tr>
            @empty
            <tr><td class="py-4 text-center text-slate-400" colspan="2">Tidak ada loss tercatat</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
