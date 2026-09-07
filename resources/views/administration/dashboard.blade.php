@extends('layouts.app')
@section('title', ' - Administration Control Center')
@section('content')
<x-ui.page-header title="Administration Control Center" description="Pusat kendali surat, invoice, pembayaran, dan kwitansi." />

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    @foreach([['Surat Bulan Ini', $lettersMonth], ['Surat Menunggu Approval', $lettersPending], ['Surat Belum Terkirim', $lettersUnsent], ['Invoice Bulan Ini', $invoicesMonth], ['Invoice Belum Lunas (Rp)', number_format($invoicesUnpaid, 0, ',', '.')], ['Invoice Overdue', $invoicesOverdue], ['Pembayaran Bulan Ini (Rp)', number_format($paymentsMonth, 0, ',', '.')], ['Kwitansi Bulan Ini', $receiptsMonth]] as [$label, $value])
    <div class="dashboard-card p-4 min-w-0"><div class="text-xs font-semibold text-slate-500 truncate">{{ $label }}</div><div class="mt-1 text-xl font-bold break-words">{{ $value }}</div></div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-2">Surat Terbaru</h2>
        @forelse($latestLetters as $l)<div class="flex justify-between gap-2 text-sm py-1.5 border-b border-slate-100"><span class="min-w-0"><span class="font-mono">{{ $l->number ?? '-' }}</span> · <span class="truncate">{{ $l->subject }}</span></span><x-status-badge :status="$l->status" /></div>@empty<div class="text-sm text-slate-400">Belum ada surat.</div>@endforelse
        <a href="{{ route('letters.index') }}" class="text-xs text-amber-600 font-semibold mt-2 inline-block">Buka Register Surat →</a>
    </div>
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-2">Invoice Overdue</h2>
        @forelse($overdueInvoices as $i)<div class="flex justify-between gap-2 text-sm py-1.5 border-b border-slate-100"><span class="font-mono">{{ $i->number }}</span><span class="text-red-600 font-semibold">{{ $i->due_date?->format('d/m/Y') }}</span></div>@empty<div class="text-sm text-slate-400">Tidak ada overdue.</div>@endforelse
        <a href="{{ route('invoice-register.index') }}" class="text-xs text-amber-600 font-semibold mt-2 inline-block">Buka Register Invoice →</a>
    </div>
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-2">Dokumen Menunggu Approval</h2>
        @forelse($pendingApprovals as $l)<div class="flex justify-between gap-2 text-sm py-1.5 border-b border-slate-100"><span class="min-w-0 truncate">{{ $l->subject }}</span><a href="{{ route('letters.show', $l) }}" class="text-amber-600 text-xs">Review</a></div>@empty<div class="text-sm text-slate-400">Tidak ada antrean.</div>@endforelse
    </div>
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-2">Kwitansi Terbaru</h2>
        @forelse($latestReceipts as $r)<div class="flex justify-between gap-2 text-sm py-1.5 border-b border-slate-100"><span class="font-mono">{{ $r->number }}</span><span>{{ number_format($r->amount, 0, ',', '.') }}</span></div>@empty<div class="text-sm text-slate-400">Belum ada kwitansi.</div>@endforelse
        <a href="{{ route('receipts.index') }}" class="text-xs text-amber-600 font-semibold mt-2 inline-block">Buka Register Kwitansi →</a>
    </div>
</div>
@endsection
