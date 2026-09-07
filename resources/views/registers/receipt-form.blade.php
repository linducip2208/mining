@extends('layouts.app')
@section('title', ' - Kwitansi Baru')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Kwitansi Baru</h1>
<p class="text-sm text-slate-500 mb-4">Wajib dari pembayaran POSTED. Kwitansi tidak menciptakan uang masuk baru.</p>
<form method="POST" action="{{ route('receipts.store') }}" class="space-y-4">
    @csrf
    <div class="bg-white rounded-xl border border-slate-200 p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Pembayaran</label>
            <select name="payment_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm" onchange="if(this.value) window.location='{{ route('receipts.create') }}?payment_id='+this.value">
                <option value="">-- pilih pembayaran POSTED --</option>
                @foreach ($payments as $p)<option value="{{ $p->id }}" @selected($payment?->id === $p->id)>{{ $p->number }} · {{ $p->customer?->name }} · Rp {{ number_format($p->amount, 0) }} ({{ $p->status }})</option>@endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="receipt_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        @if($payment)
        <div class="md:col-span-2 bg-slate-50 rounded-lg p-3 text-sm">Pembayar: <strong>{{ $payment->customer?->name ?? 'Tunai' }}</strong> · Jumlah: <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong> · Status payment: {{ $payment->status }}</div>
        @endif
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Invoice Ref (opsional)</label>
            <select name="invoice_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($invoices as $i)<option value="{{ $i->id }}">{{ $i->number }} · Rp {{ number_format($i->total, 0) }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Metode</label>
            <select name="payment_method" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach (['CASH' => 'Tunai', 'TRANSFER' => 'Transfer', 'GIRO' => 'Giro', 'DEPOSIT' => 'Deposit'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Kas/Bank</label>
            <select name="cash_account_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($cashAccounts as $c)<option value="{{ $c->id }}">{{ $c->name }} {{ $c->account_no ? '(' . $c->account_no . ')' : '' }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">No Referensi Bank</label>
            <input name="reference_no" maxlength="100" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Keterangan</label>
            <input name="description" maxlength="500" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div class="form-actions-sticky flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white">
        <button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Buat Kwitansi</button>
        <a href="{{ route('receipts.index') }}" class="px-5 py-2.5 rounded-lg bg-slate-100 text-sm min-h-[44px] inline-flex items-center">Batal</a>
    </div>
</form>
@endsection
