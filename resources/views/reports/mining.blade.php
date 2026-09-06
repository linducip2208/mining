@extends('layouts.app')
@section('title', ' - Laporan Tambang')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Produksi Tambang</h1>
    <a href="{{ url()->current() . (count(request()->query()) ? '?' . http_build_query(array_merge(request()->query(), ['export' => 1])) : '?export=1') }}" class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm print:hidden">Export CSV</a>
    <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('report.print', ['report' => 'mining']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('report.pdf', ['report' => 'mining']) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
</div>
<x-filter-bar :route="route('report.mining')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Tonase per Site</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Site</th><th class="py-2 text-right">Trip</th><th class="py-2 text-right">Tonase</th><th class="py-2 text-right">Jam Kerja</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($perSite as $row)
            <tr><td class="py-1.5">{{ $row->name }}</td><td class="py-1.5 text-right">{{ $row->trips }}</td><td class="py-1.5 text-right font-semibold">{{ number_format($row->tonnage, 2) }}</td><td class="py-1.5 text-right">{{ number_format($row->hours, 1) }}</td></tr>
            @empty <tr><td colspan="4" class="py-6 text-center text-slate-400">Tidak ada data</td></tr> @endforelse
        </tbody>
    </table>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Produktivitas Operator (Top 15)</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Operator</th><th class="py-2 text-right">Ton</th><th class="py-2 text-right">Jam</th><th class="py-2 text-right">Ton/Jam</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($perOperator as $row)
                <tr><td class="py-1.5">{{ $row->name }}</td><td class="py-1.5 text-right">{{ number_format($row->tonnage, 1) }}</td><td class="py-1.5 text-right">{{ number_format($row->hours, 1) }}</td><td class="py-1.5 text-right font-medium">{{ $row->hours > 0 ? number_format($row->tonnage / $row->hours, 2) : '-' }}</td></tr>
                @empty <tr><td colspan="4" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Produktivitas Peralatan (Top 15)</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Peralatan</th><th class="py-2 text-right">Ton</th><th class="py-2 text-right">Jam</th><th class="py-2 text-right">Ton/Jam</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($perEquipment as $row)
                <tr><td class="py-1.5">{{ $row->name }}</td><td class="py-1.5 text-right">{{ number_format($row->tonnage, 1) }}</td><td class="py-1.5 text-right">{{ number_format($row->hours, 1) }}</td><td class="py-1.5 text-right font-medium">{{ $row->hours > 0 ? number_format($row->tonnage / $row->hours, 2) : '-' }}</td></tr>
                @empty <tr><td colspan="4" class="py-6 text-center text-slate-400">-</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
