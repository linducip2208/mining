@extends('layouts.app')
@section('title', ' - Pajak')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Pajak</h1>
<div class="grid grid-cols-3 gap-4 mb-4">
    <x-stat-card title="PPN Keluaran (Aktif)" :value="'Rp ' . number_format($ppnOut, 0, ',', '.')" color="red" />
    <x-stat-card title="PPN Masukan (Aktif)" :value="'Rp ' . number_format($ppnIn, 0, ',', '.')" color="green" />
    <x-stat-card title="Net PPN (Out - In)" :value="'Rp ' . number_format($ppnOut - $ppnIn, 0, ',', '.')" color="indigo" />
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h3 class="font-semibold text-sm mb-3">Kode Pajak (tarif configurable)</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Kode</th><th class="py-2">Nama</th><th class="py-2">Tipe</th><th class="py-2 text-right">Tarif %</th><th class="py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($taxCodes as $code)
            <tr><td class="py-1.5 font-medium">{{ $code->code }}</td><td class="py-1.5">{{ $code->name }}</td><td class="py-1.5 text-xs">{{ $code->type }}</td><td class="py-1.5 text-right">{{ number_format($code->rate, 2) }}%</td><td class="py-1.5"><x-status-badge :status="$code->status ? 'ACTIVE' : 'INACTIVE'" /></td></tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="bg-white rounded-xl border border-slate-200">
    <div class="px-5 py-3 border-b border-slate-100 font-semibold text-sm">Transaksi Pajak</div>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="px-5 py-2">Tanggal</th><th class="px-5 py-2">Jenis</th><th class="px-5 py-2">No Faktur Pajak</th><th class="px-5 py-2 text-right">Dasar</th><th class="px-5 py-2 text-right">Pajak</th><th class="px-5 py-2">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($items as $item)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-2">{{ $item->trx_date?->format('d/m/Y') }}</td>
                <td class="px-5 py-2 text-xs">{{ $item->kind }} · {{ $item->taxCode?->code }}</td>
                <td class="px-5 py-2 text-xs">{{ $item->tax_invoice_no ?? $item->transaction_number }}</td>
                <td class="px-5 py-2 text-right">{{ number_format($item->tax_base, 0, ',', '.') }}</td>
                <td class="px-5 py-2 text-right font-semibold">{{ number_format($item->tax_amount, 0, ',', '.') }}</td>
                <td class="px-5 py-2"><x-status-badge :status="$item->status" /></td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada transaksi pajak</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection