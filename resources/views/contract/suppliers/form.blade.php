@extends('layouts.app')

@section('title', ' - Buat Kontrak Supplier')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Kontrak Supplier</h1>
</div>

<form method="POST" action="{{ route('supplier-contracts.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
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
            <label class="text-xs font-semibold text-slate-600 uppercase">Supplier</label>
            <select name="supplier_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($suppliers ?? [] as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Item (opsional)</label>
            <select name="item_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Jasa / umum --</option>
                @foreach ($items ?? [] as $it)
                    <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">Deskripsi Jasa</label>
            <input type="text" name="service_description" value="{{ old('service_description') }}" maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Qty Kontrak</label>
            <input type="number" step="0.01" min="0" name="contract_qty" value="{{ old('contract_qty') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nilai Kontrak</label>
            <input type="number" step="0.01" min="0" name="contract_value" value="{{ old('contract_value') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Harga Satuan</label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Mulai</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Berakhir</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Termin Bayar</label>
            <select name="payment_term_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($paymentTerms ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">SLA</label>
            <textarea name="sla" rows="2" maxlength="2000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">{{ old('sla') }}</textarea>
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Draft</button>
        <a href="{{ route('supplier-contracts.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
