@extends('layouts.app')

@section('title', ' - Buat Laporan HSE')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Buat Laporan HSE</h1>
</div>

<form method="POST" action="{{ route('hse.reports.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
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
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jenis</label>
            <select name="kind" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="INCIDENT">Insiden</option>
                <option value="NEAR_MISS">Near Miss</option>
                <option value="HAZARD">Laporan Bahaya</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Waktu Kejadian</label>
            <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Lokasi</label>
            <input type="text" name="location" value="{{ old('location') }}" maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Severity</label>
            <select name="severity" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                @foreach ($severities ?? ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'] as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Karyawan Terkait</label>
            <select name="employee_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($employees ?? [] as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Unit Terkait</label>
            <select name="equipment_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($units ?? [] as $u)
                    <option value="{{ $u->id }}">{{ $u->code }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Foto</label>
            <input type="file" name="photo" accept="image/*" class="mt-1 w-full text-sm">
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">Deskripsi</label>
            <textarea name="description" rows="3" required maxlength="5000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">{{ old('description') }}</textarea>
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">Penyebab</label>
            <textarea name="cause" rows="2" maxlength="5000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">{{ old('cause') }}</textarea>
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">Tindakan Segera</label>
            <textarea name="immediate_action" rows="2" maxlength="5000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">{{ old('immediate_action') }}</textarea>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Latitude</label>
            <input type="number" step="any" name="latitude" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Longitude</label>
            <input type="number" step="any" name="longitude" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Laporan</button>
        <a href="{{ route('hse.reports.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
