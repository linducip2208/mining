@extends('layouts.app')

@section('title', ' - Buat Kontrak Hauling')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Kontrak Hauling</h1>
</div>

<form method="POST" action="{{ route('hauling-contracts.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
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
            <label class="text-xs font-semibold text-slate-600 uppercase">Kontraktor</label>
            <select name="supplier_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($suppliers ?? [] as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Rute</label>
            <select name="hauling_route_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($routes ?? [] as $r)
                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->distance_km }} km)</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tipe Tarif</label>
            <select name="rate_type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="PER_TON">Per Ton</option>
                <option value="PER_KM">Per Km</option>
                <option value="PER_TRIP">Per Trip</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tarif (Rp)</label>
            <input type="number" step="0.01" min="0" name="rate" value="{{ old('rate') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Volume Minimum</label>
            <input type="number" step="0.01" min="0" name="minimum_volume" value="{{ old('minimum_volume') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Mulai</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Berakhir</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Draft</button>
        <a href="{{ route('hauling-contracts.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
