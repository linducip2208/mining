@extends('layouts.app')
@section('title', ' - Terima Pembayaran')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Terima Pembayaran Customer</h1>
<form method="POST" action="{{ route('payments.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-3 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Customer</label>
            <select name="customer_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Metode</label>
            <select name="method" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="BANK_TRANSFER">Transfer Bank</option><option value="CASH">Tunai</option><option value="CHECK">Cek</option><option value="GIRO">Giro</option><option value="VIRTUAL_ACCOUNT">Virtual Account</option>
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Kas/Bank</label>
            <select name="cash_account_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($cashAccounts as $ca)<option value="{{ $ca->id }}">{{ $ca->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Jumlah (Rp)</label>
            <input type="number" step="0.01" name="amount" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">No Referensi</label>
            <input name="reference_no" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <p class="text-xs text-slate-400 mt-3">Alokasi otomatis ke faktur outstanding tertua (FIFO).</p>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('payments.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection