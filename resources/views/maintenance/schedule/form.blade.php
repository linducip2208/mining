@extends('layouts.app')
@section('title', ' - Jadwal')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Jadwal Pemeliharaan</h1>
<form method="POST" action="{{ route('maintenance-schedules.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Nama Jadwal</label>
            <input name="name" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Peralatan</label>
            <select name="equipment_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($equipment as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Berdasarkan</label>
            <select name="interval_type" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="RUNNING_HOUR">Jam Kerja</option><option value="KM">Kilometer</option><option value="DAY">Hari</option><option value="MONTH">Bulan</option>
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Setiap</label>
            <input type="number" name="interval_value" required min="1" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Terakhir Dilakukan</label>
            <input type="date" name="last_done" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('maintenance-schedules.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection