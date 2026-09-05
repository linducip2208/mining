@extends('layouts.app')
@section('title', ' - Lembur')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Catat Lembur</h1>
<form method="POST" action="{{ route('overtimes.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Karyawan</label>
            <select name="employee_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Jam</label>
            <input type="number" step="0.5" min="0.5" max="24" name="hours" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Alasan</label>
            <input name="reason" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('overtimes.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection