@extends('layouts.app')
@section('title', ' - Neraca')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Neraca (Balance Sheet)</h1>
    <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('finance.balance_sheet.print', request()->query()) }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('finance.balance_sheet.pdf', request()->query()) }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
</div>
<x-filter-bar :route="route('finance.balance_sheet')" class="print:hidden">
    <x-filter-input name="as_of" label="Per" type="date" />
</x-filter-bar>
<div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="font-bold text-center mb-4">ASET</div>
        <table class="w-full text-sm">
            @foreach ($assets as $a)
            <tr><td class="py-1">{{ $a->code }} - {{ $a->name }}</td><td class="py-1 text-right">{{ number_format($a->balance, 0, ',', '.') }}</td></tr>
            @endforeach
            <tr class="border-t-2 font-bold"><td class="pt-2">TOTAL ASET</td><td class="pt-2 text-right">{{ number_format($totalAssets, 0, ',', '.') }}</td></tr>
        </table>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="font-bold text-center mb-4">KEWAJIBAN & EKUITAS</div>
        <table class="w-full text-sm">
            <tr class="uppercase text-[11px] text-slate-400"><td colspan="2">Kewajiban</td></tr>
            @foreach ($liabilities as $l)
            <tr><td class="py-1 pl-2">{{ $l->code }} - {{ $l->name }}</td><td class="py-1 text-right">{{ number_format($l->balance, 0, ',', '.') }}</td></tr>
            @endforeach
            <tr class="uppercase text-[11px] text-slate-400"><td colspan="2" class="pt-3">Ekuitas</td></tr>
            @foreach ($equity as $e)
            <tr><td class="py-1 pl-2">{{ $e->code }} - {{ $e->name }}</td><td class="py-1 text-right">{{ number_format($e->balance, 0, ',', '.') }}</td></tr>
            @endforeach
            <tr><td class="py-1 pl-2">Laba Tahun Berjalan</td><td class="py-1 text-right">{{ number_format($netIncome, 0, ',', '.') }}</td></tr>
            <tr class="border-t-2 font-bold"><td class="pt-2">TOTAL KEWAJIBAN + EKUITAS</td><td class="pt-2 text-right">{{ number_format($totalLiab + $totalEquity, 0, ',', '.') }}</td></tr>
        </table>
    </div>
</div>
<div class="mt-4 text-center">
    @if ($balanced)
    <span class="text-green-600 font-semibold">✓ ASET = KEWAJIBAN + EKUITAS (Balanced)</span>
    @else
    <span class="text-red-600 font-semibold">✗ SELISIH: Rp {{ number_format($totalAssets - ($totalLiab + $totalEquity), 2) }}</span>
    @endif
</div>
@endsection
