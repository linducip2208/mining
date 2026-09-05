@extends('layouts.app')
@section('title', ' - Neraca Saldo')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Neraca Saldo (Trial Balance)</h1>
    <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm print:hidden">Cetak / PDF</button>
</div>
<x-filter-bar :route="route('finance.trial_balance')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="mb-4 flex justify-between">
        <div class="font-semibold">Periode: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
        @if ($balanced)
        <span class="text-green-600 font-semibold text-sm">✓ BALANCE</span>
        @else
        <span class="text-red-600 font-semibold text-sm">✗ TIDAK BALANCE</span>
        @endif
    </div>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b-2 border-slate-200">
            <th class="py-2">Kode</th><th class="py-2">Nama Akun</th><th class="py-2">Tipe</th>
            <th class="py-2 text-right">Debit</th><th class="py-2 text-right">Kredit</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $row)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ $row->code }}</td>
                <td class="py-1.5">{{ $row->name }}</td>
                <td class="py-1.5 text-xs text-slate-400">{{ $row->type }}</td>
                <td class="py-1.5 text-right">{{ number_format($row->debit, 2, ',', '.') }}</td>
                <td class="py-1.5 text-right">{{ number_format($row->credit, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="py-8 text-center text-slate-400">Tidak ada transaksi pada periode ini</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-slate-300 font-bold">
                <td colspan="3" class="py-2">TOTAL</td>
                <td class="py-2 text-right">{{ number_format($totalDebit, 2, ',', '.') }}</td>
                <td class="py-2 text-right">{{ number_format($totalCredit, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection