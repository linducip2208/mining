@extends('layouts.app')
@section('title', ' - Insentif')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Buat Insentif Operator</h1>
<form method="POST" action="{{ route('operator-incentives.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Karyawan</label>
            <select name="employee_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Basis</label>
            <select name="basis" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                <option value="TONNAGE">Tonase</option><option value="SHIFT">Shift</option><option value="ACTIVITY">Aktivitas</option><option value="EQUIPMENT">Alat</option><option value="TARGET">Target</option>
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Periode (YYYY-MM)</label>
            <input name="period" value="{{ now()->format('Y-m') }}" required pattern="\d{4}-\d{2}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Qty (Ton/etc)</label>
            <input type="number" step="0.01" name="quantity" required id="qty" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Rate per Unit (Rp)</label>
            <input type="number" step="0.01" name="rate" required id="rate" oninput="document.getElementById('preview').textContent = (parseFloat(this.value||0) * parseFloat(document.getElementById('qty').value||0)).toLocaleString('id-ID')" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Preview</label>
            <div id="preview" class="mt-1 px-3 py-2 font-bold text-amber-600">0</div></div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('operator-incentives.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection