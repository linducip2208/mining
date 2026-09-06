@extends('layouts.app')

@section('title', ' - Buat Issue BBM')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Issue BBM</h1>
</div>

<form method="POST" action="{{ route('fuel-issues.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
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
                @foreach ($sites ?? [] as $s)
                    <option value="{{ $s->id }}" @selected(old('site_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="issue_date" value="{{ old('issue_date', today()->toDateString()) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tangki</label>
            <select name="fuel_tank_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($tanks ?? [] as $t)
                    <option value="{{ $t->id }}" @selected(old('fuel_tank_id') == $t->id)>{{ $t->code }} — {{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Unit</label>
            <select name="equipment_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($units ?? [] as $u)
                    <option value="{{ $u->id }}" @selected(old('equipment_id') == $u->id)>{{ $u->code }} — {{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Plat (non-unit)</label>
            <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate') }}" maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Operator</label>
            <select name="operator_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($operators ?? [] as $o)
                    <option value="{{ $o->id }}" @selected(old('operator_id') == $o->id)>{{ $o->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Shift</label>
            <select name="shift_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($shifts ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Liter</label>
            <input type="number" step="0.001" min="0.001" name="liter" value="{{ old('liter') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">HM Sebelum</label>
            <input type="number" step="0.01" min="0" name="hm_before" value="{{ old('hm_before') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">HM Sesudah</label>
            <input type="number" step="0.01" min="0" name="hm_after" value="{{ old('hm_after') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Referensi</label>
            <input type="text" name="reference" value="{{ old('reference') }}" maxlength="100" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
            <input type="text" name="notes" value="{{ old('notes') }}" maxlength="1000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Draft</button>
        <a href="{{ route('fuel-issues.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
