@extends('layouts.app')

@section('title', ' - Kontrak ' . $contract->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('supplier-contracts.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">Kontrak {{ $contract->number }} <x-status-badge :status="$contract->status" /></h1>
    <p class="text-sm text-slate-500">{{ $contract->supplier?->name }} · {{ $contract->item?->name ?? $contract->service_description }}</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="grid md:grid-cols-4 gap-3 text-sm">
        <div><div class="text-[11px] uppercase text-slate-500">Nilai Kontrak</div><div class="font-bold">Rp {{ number_format($contract->contract_value ?? 0, 0) }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Harga Satuan</div><div class="font-semibold">Rp {{ number_format($contract->price, 0) }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Periode</div><div class="font-semibold">{{ $contract->start_date?->format('Y-m-d') }} → {{ $contract->end_date?->format('Y-m-d') }}</div></div>
        <div><div class="text-[11px] uppercase text-slate-500">Realisasi PO</div><div class="font-semibold">Rp {{ number_format($real['po_value'] ?? $real['realized_value'] ?? 0, 0) }}</div></div>
    </div>
    @if ($contract->sla)
    <div class="mt-3 text-sm"><div class="text-[11px] uppercase text-slate-500">SLA</div><div class="text-slate-700">{{ $contract->sla }}</div></div>
    @endif
    @can('contract.approve')
    @if ($contract->status === 'DRAFT')
    <form method="POST" action="{{ route('supplier-contracts.approve', $contract) }}" class="mt-4" onsubmit="return confirm('Aktifkan kontrak ini?')">
        @csrf<button class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">Aktifkan Kontrak</button>
    </form>
    @endif
    @endcan
</div>
@endsection
