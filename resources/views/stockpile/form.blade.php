@extends('layouts.app')

@section('title', ' - Tambah Stockpile')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Tambah Stockpile</h1>
</div>

<form method="POST" action="{{ route('stockpiles.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
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
            <select name="site_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? [] as $s)
                    <option value="{{ $s->id }}" @selected(old('site_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input type="text" name="code" value="{{ old('code') }}" required maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="150" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
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
            <label class="text-xs font-semibold text-slate-600 uppercase">Gudang</label>
            <select name="warehouse_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($warehouses ?? [] as $w)
                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kapasitas (Ton)</label>
            <input type="number" step="0.01" min="0" name="capacity_ton" value="{{ old('capacity_ton') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Threshold Survei (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="survey_threshold_pct" value="{{ old('survey_threshold_pct') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Saldo Awal (Ton)</label>
            <input type="number" step="0.01" min="0" name="opening" value="{{ old('opening') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('stockpiles.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
