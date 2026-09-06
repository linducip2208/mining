@extends('layouts.app')

@section('title', ' - Kontrak ' . $contract->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('customer-contracts.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">Kontrak {{ $contract->number }} <x-status-badge :status="$contract->status" /></h1>
    <p class="text-sm text-slate-500">{{ $contract->customer?->name }} · {{ $contract->item?->name }} · {{ $contract->start_date?->format('Y-m-d') }} → {{ $contract->end_date?->format('Y-m-d') }}</p>
</div>

<div class="grid md:grid-cols-5 gap-3 mb-4">
    <x-stat-card title="Volume Kontrak" :value="number_format($real['contract_qty'] ?? 0, 1) . ' T'" color="slate" />
    <x-stat-card title="Sudah Dipesan" :value="number_format($real['ordered_qty'] ?? 0, 1) . ' T'" color="blue" />
    <x-stat-card title="Terkirim" :value="number_format($real['delivered_qty'] ?? 0, 1) . ' T'" color="green" />
    <x-stat-card title="Tertagih" :value="number_format($real['invoiced_qty'] ?? 0, 1) . ' T'" color="indigo" />
    <x-stat-card title="Sisa" :value="number_format($real['remaining_qty'] ?? 0, 1) . ' T'" color="amber" />
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="grid md:grid-cols-3 gap-3 text-sm">
        <div><div class="text-[11px] uppercase text-slate-500">Nilai Kontrak</div><div class="font-bold">Rp {{ number_format($real['contract_value'] ?? 0, 0) }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Harga / Ton</div><div class="font-semibold">Rp {{ number_format($contract->price, 0) }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Termin / Delivery</div><div class="font-semibold">{{ $contract->paymentTerm?->name ?? '—' }} / {{ $contract->delivery_term ?? '—' }}</div></div>
    </div>
    @can('contract.approve')
    @if ($contract->status === 'DRAFT')
    <form method="POST" action="{{ route('customer-contracts.approve', $contract) }}" class="mt-4" onsubmit="return confirm('Aktifkan kontrak ini?')">
        @csrf<button class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">Aktifkan Kontrak</button>
    </form>
    @endif
    @endcan
</div>
@endsection
