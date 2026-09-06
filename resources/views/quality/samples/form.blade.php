@extends('layouts.app')

@section('title', ' - Buat Sampel QC')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Sampel QC</h1>
</div>

<form method="POST" action="{{ route('samples.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jenis Sumber</label>
            <select name="source_type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="PRODUCTION_BATCH">Batch Produksi</option>
                <option value="STOCKPILE">Stockpile</option>
                <option value="SALES_ORDER">Sales Order</option>
                <option value="DELIVERY_ORDER">Surat Jalan</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">ID Sumber</label>
            <input type="number" min="1" name="source_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Material</label>
            <select name="item_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($items ?? [] as $it)
                    <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Customer (opsional)</label>
            <select name="customer_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($customers ?? [] as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal Sampel</label>
            <input type="date" name="sample_date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Buat Sampel</button>
        <a href="{{ route('samples.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
