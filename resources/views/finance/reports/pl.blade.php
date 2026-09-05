@extends('layouts.app')
@section('title', ' - Laba Rugi')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Laporan Laba Rugi</h1>
    <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
</div>
<x-filter-bar :route="route('finance.pl')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    <div class="text-center mb-6">
        <div class="font-bold text-lg">LAPORAN LABA RUGI</div>
        <div class="text-sm text-slate-400">Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
    </div>
    <table class="w-full text-sm">
        <tr class="uppercase text-[11px] text-slate-400"><td colspan="2" class="pb-1">Pendapatan</td></tr>
        @foreach ($revenues as $r)
        <tr><td class="py-1 pl-4">{{ $r->code }} - {{ $r->name }}</td><td class="py-1 text-right">Rp {{ number_format($r->balance, 0, ',', '.') }}</td></tr>
        @endforeach
        <tr class="border-b font-semibold"><td class="py-1.5">Total Pendapatan</td><td class="py-1.5 text-right">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td></tr>
        <tr class="uppercase text-[11px] text-slate-400"><td colspan="2" class="pt-3 pb-1">Beban</td></tr>
        @foreach ($expenses as $e)
        <tr><td class="py-1 pl-4">{{ $e->code }} - {{ $e->name }}</td><td class="py-1 text-right">Rp {{ number_format($e->balance, 0, ',', '.') }}</td></tr>
        @endforeach
        <tr class="border-b font-semibold"><td class="py-1.5">Total Beban</td><td class="py-1.5 text-right">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td></tr>
        <tr class="{{ $netIncome >= 0 ? 'text-green-700' : 'text-red-600' }} font-bold text-base">
            <td class="pt-3">LABA / (RUGI) BERSIH</td>
            <td class="pt-3 text-right">Rp {{ number_format($netIncome, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>
@endsection