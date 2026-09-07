@extends('layouts.app')
@php use App\Services\NumberToWordsService; @endphp
@section('title', ' - Register Kwitansi')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Register Kwitansi</h1>
        <p class="text-sm text-slate-500">Selalu terhubung ke pembayaran yang benar-benar ada</p>
    </div>
    @can('receipt.create')<a href="{{ route('receipts.create') }}" class="px-4 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[38px] inline-flex items-center">+ Kwitansi</a>@endcan
</div>

<x-filter-bar :route="route('receipts.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor / pembayar..." />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

@if($receipt)
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold font-mono">{{ $receipt->number }}</h2>
            <p class="text-sm text-slate-500">{{ $receipt->receipt_date?->format('d/m/Y') }} · {{ $receipt->payment_method }} {{ $receipt->reference_no ? '(' . $receipt->reference_no . ')' : '' }}</p>
        </div>
        <x-status-badge :status="$receipt->status" />
    </div>
    <div class="grid md:grid-cols-2 gap-4 mt-4 text-sm">
        <div>
            <div class="text-xs font-semibold text-slate-400 uppercase">Diterima Dari</div>
            <div class="font-semibold">{{ $receipt->payer_name }}</div>
            <div class="text-slate-500">{{ $receipt->description }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-slate-400 uppercase">Referensi</div>
            <div>Payment: @if($receipt->payment)<a class="text-amber-600 hover:underline" href="{{ route('payments.show', $receipt->payment) }}">{{ $receipt->payment->number }}</a>@else - @endif</div>
            <div>Invoice: {{ $receipt->invoice?->number ?? '-' }}</div>
            <div>Bank: {{ $receipt->cashAccount?->bank_name }} {{ $receipt->cashAccount?->account_no }}</div>
        </div>
    </div>
    <div class="mt-3 text-right">
        <div class="text-2xl font-bold">Rp {{ number_format($receipt->amount, 0, ',', '.') }}</div>
        <div class="text-sm text-slate-500 italic">Terbilang: {{ $terbilang }}</div>
    </div>
    <div class="text-xs text-slate-400 mt-2">Dibuat oleh {{ $receipt->creator?->name }} · Disetujui {{ $receipt->approver?->name ?? '-' }} · Dicetak {{ $receipt->printed_at?->format('d/m/Y H:i') ?? '-' }}</div>
    <div class="flex flex-wrap gap-2 mt-4">
        @if($receipt->status === 'DRAFT')
        <form action="{{ route('receipts.issue', $receipt) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[38px]">Terbitkan</button></form>
        @endif
        @if($receipt->status === 'ISSUED')
        <form action="{{ route('receipts.confirm', $receipt) }}" method="POST">@csrf<button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold min-h-[38px]">Konfirmasi Lunas</button></form>
        @endif
        <a href="{{ route('receipts.print', $receipt) }}" target="_blank" class="px-4 py-2 rounded-lg border border-slate-200 text-sm min-h-[38px] inline-flex items-center">Cetak Kwitansi</a>
        @if($receipt->status !== 'VOID')
        <form action="{{ route('receipts.void', $receipt) }}" method="POST" onsubmit="return confirm('Void kwitansi? Pembayaran asal tidak dihapus.')">@csrf<button class="px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm min-h-[38px]">Void</button></form>
        @endif
    </div>
</div>
@endif

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Pembayar</th>
        <th class="px-4 py-2.5 text-right">Jumlah</th><th class="px-4 py-2.5">Metode</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-mono font-medium whitespace-nowrap">{{ $item->number }}</td>
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $item->receipt_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->payer_name }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">{{ number_format($item->amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->payment_method }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right"><a href="{{ route('receipts.show', $item) }}" class="text-amber-600 hover:underline text-xs">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada kwitansi</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
