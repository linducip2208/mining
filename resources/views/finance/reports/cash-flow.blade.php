@extends('layouts.app')

@section('title', ' - Arus Kas')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Arus Kas (Cash Flow — berbasis buku kas)</h1>
    <span class="print:hidden inline-flex items-center gap-2"><a href="{{ route('finance.cash_flow.print', request()->query()) }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm">Cetak</a><a href="{{ route('finance.cash_flow.pdf', request()->query()) }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm">PDF</a></span>
</div>
<x-filter-bar :route="route('finance.cash_flow')" class="print:hidden">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

@php
    $labels = [
        'PAYMENT' => 'Pembayaran Customer',
        'CUSTOMER_DEPOSIT' => 'Deposit Customer',
        'VENDOR_BILL' => 'Pembayaran Supplier',
        'PAYROLL_PAYMENT' => 'Pembayaran Gaji',
        'CSR' => 'Beban CSR',
        'OPENING' => 'Saldo Awal / Setoran Modal',
        'MANUAL' => 'Jurnal Manual',
        'SALES_INVOICE' => 'Faktur Penjualan',
        'REFUND' => 'Refund Deposit',
    ];
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    <div class="text-center mb-6">
        <div class="font-bold text-lg">LAPORAN ARUS KAS</div>
        <div class="text-sm text-slate-400">Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
    </div>
    <table class="w-full text-sm">
        <tr class="border-b"><td class="py-2">Saldo Kas & Bank Awal</td><td class="py-2 text-right font-semibold">Rp {{ number_format($opening, 0, ',', '.') }}</td></tr>
        @forelse ($inflows as $row)
        <tr class="hover:bg-slate-50">
            <td class="py-2 pl-4">{{ $labels[$row->source_type] ?? ($row->source_type ?? 'Lainnya') }}</td>
            <td class="py-2 text-right {{ $row->net >= 0 ? 'text-green-700' : 'text-red-600' }}">
                {{ $row->net >= 0 ? '+' : '−' }} Rp {{ number_format(abs($row->net), 0, ',', '.') }}
            </td>
        </tr>
        @empty
        <tr><td colspan="2" class="py-6 text-center text-slate-400">Tidak ada mutasi kas pada periode ini</td></tr>
        @endforelse
        <tr class="border-t-2 border-slate-300 font-bold text-base {{ $closing >= 0 ? 'text-slate-800' : 'text-red-600' }}">
            <td class="pt-3">SALDO KAS & BANK AKHIR</td>
            <td class="pt-3 text-right">Rp {{ number_format($closing, 0, ',', '.') }}</td>
        </tr>
    </table>
    <p class="text-xs text-slate-400 mt-4">Sumber: jurnal berstatus POSTED yang menyentuh akun ber-subtype CASH/BANK (single source of truth).</p>
</div>
@endsection
