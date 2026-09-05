@extends('layouts.app')
@section('title', ' - Buku Besar')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buku Besar</h1>
<x-filter-bar :route="route('finance.ledger')">
    <x-filter-input name="chart_of_account_id" label="Akun" type="select" :options="$coas->pluck('name','id')->all()" placeholder="Pilih akun..." />
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
@if ($coa)
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="font-semibold mb-3">{{ $coa->code }} - {{ $coa->name }}</div>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b-2 border-slate-200">
            <th class="py-2">Tanggal</th><th class="py-2">Jurnal</th><th class="py-2">Keterangan</th>
            <th class="py-2 text-right">Debit</th><th class="py-2 text-right">Kredit</th><th class="py-2 text-right">Saldo</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($lines as $line)
            <tr class="hover:bg-slate-50">
                <td class="py-1.5">{{ \Carbon\Carbon::parse($line->journal_date)->format('d/m/Y') }}</td>
                <td class="py-1.5">{{ $line->number }}</td>
                <td class="py-1.5 text-xs">{{ $line->memo }}</td>
                <td class="py-1.5 text-right">{{ $line->debit > 0 ? number_format($line->debit, 2, ',', '.') : '' }}</td>
                <td class="py-1.5 text-right">{{ $line->credit > 0 ? number_format($line->credit, 2, ',', '.') : '' }}</td>
                <td class="py-1.5 text-right font-semibold">{{ number_format($line->balance, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-8 text-center text-slate-400">Tidak ada transaksi</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@else
<div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">Pilih akun untuk melihat buku besar</div>
@endif
@endsection