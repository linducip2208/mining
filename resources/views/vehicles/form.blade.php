@extends('layouts.app')

@section('title', ' - Kendaraan')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Kendaraan</h1>
</div>

<form method="POST" action="{{ $item ? route('vehicles.update', $item) : route('vehicles.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}" @selected(($item->company_id ?? old('company_id')) == $id)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">No Polisi</label>
            <input type="text" name="plate_no" value="{{ $item->plate_no ?? old('plate_no') }}" required maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required maxlength="150" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <input type="text" name="type" value="{{ $item->type ?? old('type') }}" required maxlength="30" placeholder="LV / BUS / AMBULANCE" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kapasitas (Ton)</label>
            <input type="number" step="0.01" min="0" name="capacity_ton" value="{{ $item->capacity_ton ?? old('capacity_ton') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
            <select name="status" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                @foreach (['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'MAINTENANCE' => 'Pemeliharaan'] as $v => $l)
                    <option value="{{ $v }}" @selected(($item->status ?? old('status', 'ACTIVE')) == $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('vehicles.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
