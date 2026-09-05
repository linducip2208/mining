@extends('layouts.app')
@section('title', ' - Cuti')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Pengajuan Cuti</h1>
<form method="POST" action="{{ route('leaves.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Karyawan</label>
            <select name="employee_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="ANNUAL">Tahunan</option><option value="SICK">Sakit</option><option value="UNPAID">Tanpa Gaji</option><option value="MATERNITY">Melahirkan</option><option value="OTHER">Lainnya</option>
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Mulai</label>
            <input type="date" name="start_date" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Sampai</label>
            <input type="date" name="end_date" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Alasan</label>
            <textarea name="reason" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></textarea></div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('leaves.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection