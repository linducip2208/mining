@extends('layouts.app')

@section('title', ' - Kategori Alat')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Kategori Alat</h1>
</div>

<form method="POST" action="{{ $item ? route('equipment-categories.update', $item) : route('equipment-categories.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required maxlength="20" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required maxlength="100" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                @foreach (['HEAVY' => 'Alat Berat', 'VEHICLE' => 'Kendaraan', 'SUPPORT' => 'Support'] as $v => $l)
                    <option value="{{ $v }}" @selected(($item->type ?? old('type')) == $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
            <select name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="1" @selected(($item->status ?? old('status', 1)) == 1)>Aktif</option>
                <option value="0" @selected(($item->status ?? old('status')) == 0)>Nonaktif</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Standar Konsumsi (L/H)</label>
            <input type="number" step="0.01" min="0" name="standard_fuel_lph" value="{{ $item->standard_fuel_lph ?? old('standard_fuel_lph') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Threshold Warning (%)</label>
            <input type="number" step="0.01" min="0" max="500" name="fuel_warning_pct" value="{{ $item->fuel_warning_pct ?? old('fuel_warning_pct') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('equipment-categories.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
