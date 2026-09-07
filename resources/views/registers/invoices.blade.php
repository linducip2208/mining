@extends('layouts.app')
@php use App\Http\Controllers\InvoiceRegisterController; use App\Services\NumberToWordsService; @endphp
@section('title', ' - Register Invoice')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Register Invoice</h1>
        <p class="text-sm text-slate-500">Sumber: Sales Invoice (tidak ada pembukuan ganda)</p>
    </div>
</div>

<x-filter-bar :route="route('invoice-register.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor invoice..." />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="customer_id" label="Customer" type="select" :options="$customers->pluck('name', 'id')->all()" />
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

@if($invoice)
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold font-mono">{{ $invoice->number }}</h2>
            <p class="text-sm text-slate-500">{{ $invoice->invoice_date?->format('d/m/Y') }} · Jatuh tempo {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100">{{ InvoiceRegisterController::displayStatus($invoice) }}</span>
    </div>
    <div class="grid md:grid-cols-2 gap-4 mt-4 text-sm">
        <div>
            <div class="text-xs font-semibold text-slate-400 uppercase">Customer</div>
            <div class="font-semibold">{{ $invoice->customer?->name }}</div>
            <div class="text-slate-500">{{ $invoice->customer?->address }} {{ $invoice->customer?->city }}</div>
            <div class="text-slate-500">NPWP: {{ $invoice->customer?->npwp ?? '-' }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-slate-400 uppercase">Pembayaran</div>
            <div>Termin: {{ $invoice->paymentTerm?->name ?? '-' }} · Due: {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</div>
            <div>Dibayar: Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</div>
            <div>Sisa: Rp {{ number_format($invoice->total - $invoice->paid_amount, 0, ',', '.') }}</div>
        </div>
    </div>
    <table class="w-full text-sm mt-4">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Produk</th><th class="py-2 text-right">Qty</th><th class="py-2">Unit</th><th class="py-2 text-right">Harga</th><th class="py-2 text-right">Total</th></tr></thead>
        <tbody>
            @foreach($invoice->items as $it)
            <tr class="border-b border-slate-100"><td class="py-2">{{ $it->item?->name }}</td><td class="py-2 text-right">{{ number_format($it->qty, 2) }}</td><td class="py-2">{{ $it->item?->unit?->code }}</td><td class="py-2 text-right">{{ number_format($it->unit_price, 0) }}</td><td class="py-2 text-right">{{ number_format($it->total_price, 0) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-3 text-sm space-y-1 text-right">
        <div>Subtotal: Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</div>
        <div>PPN: Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</div>
        <div class="text-lg font-bold">Total: Rp {{ number_format($invoice->total, 0, ',', '.') }}</div>
        <div class="text-slate-500 italic">Terbilang: {{ NumberToWordsService::rupiah((float) $invoice->total) }}</div>
    </div>
    @if($invoice->salesOrder)
    <div class="mt-3 text-xs text-slate-500">Dokumen terkait: SO <a class="text-amber-600 hover:underline" href="{{ route('sales-orders.show', $invoice->salesOrder) }}">{{ $invoice->salesOrder->number }}</a></div>
    @endif
    <div class="mt-3">
        <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Riwayat Cetak</div>
        @forelse($prints ?? [] as $p)
        <div class="text-xs text-slate-500">{{ $p->created_at?->format('d/m/Y H:i') }} · {{ $p->action }} oleh {{ $p->user?->name ?? 'sistem' }}</div>
        @empty
        <div class="text-xs text-slate-400">Belum pernah dicetak.</div>
        @endforelse
    </div>
    <div class="flex flex-wrap gap-2 mt-4">
        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm min-h-[38px] inline-flex items-center">Cetak Invoice</a>
        <a href="{{ route('invoices.show', $invoice) }}" class="px-4 py-2 rounded-lg border border-slate-200 text-sm min-h-[38px] inline-flex items-center">Buka di Sales</a>
    </div>
</div>
@endif

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Customer</th>
        <th class="px-4 py-2.5 text-right">Total</th><th class="px-4 py-2.5 text-right">Dibayar</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-mono font-medium whitespace-nowrap">{{ $item->number }}</td>
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $item->invoice_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->customer?->name }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">{{ number_format($item->total, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">{{ number_format($item->paid_amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><span class="px-2 py-1 rounded text-[11px] font-bold {{ InvoiceRegisterController::displayStatus($item) === 'LUNAS' ? 'bg-emerald-100 text-emerald-700' : (InvoiceRegisterController::displayStatus($item) === 'OVERDUE' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">{{ InvoiceRegisterController::displayStatus($item) }}</span></td>
            <td class="px-4 py-2.5 text-right"><a href="{{ route('invoice-register.show', $item) }}" class="text-amber-600 hover:underline text-xs">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada invoice</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
