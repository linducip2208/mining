@extends('layouts.app')

@section('title', ' - Buat Penerimaan BBM')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Penerimaan BBM</h1>
</div>

<form method="POST" action="{{ route('fuel-receipts.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}" @selected(old('company_id') == $id)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="receipt_date" value="{{ old('receipt_date', today()->toDateString()) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tangki Tujuan</label>
            <select name="fuel_tank_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($tanks ?? [] as $t)
                    <option value="{{ $t->id }}">{{ $t->code }} — {{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Supplier</label>
            <select name="supplier_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($suppliers ?? [] as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">PO (opsional)</label>
            <select name="purchase_order_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Tidak ada --</option>
                @foreach ($pos ?? [] as $p)
                    <option value="{{ $p->id }}">{{ $p->number }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Liter</label>
            <input type="number" step="0.001" min="0.001" name="liter" value="{{ old('liter') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Harga / Liter</label>
            <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Surat Jalan</label>
            <input type="text" name="delivery_note" value="{{ old('delivery_note') }}" maxlength="100" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Draft</button>
        <a href="{{ route('fuel-receipts.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
