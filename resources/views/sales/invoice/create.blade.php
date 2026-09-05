@extends('layouts.app')
@section('title', ' - Buat Faktur')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Faktur dari SO</h1>
<form method="POST" action="{{ route('invoices.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    @csrf
    <div class="space-y-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Sales Order (sudah terkirim)</label>
            <select name="sales_order_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach ($salesOrders as $so)<option value="{{ $so->id }}">{{ $so->number }} - {{ $so->customer?->name }}</option>@endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal Faktur</label>
            <input type="date" name="invoice_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="use_deposit" value="1" class="rounded text-amber-500">
            Gunakan deposit customer untuk pelunasan otomatis
        </label>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Buat & Posting Faktur</button>
        <a href="{{ route('invoices.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection