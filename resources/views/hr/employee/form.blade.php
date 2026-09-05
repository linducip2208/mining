@extends('layouts.app')
@section('title', ' - Karyawan')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">{{ $employee ? 'Edit' : 'Tambah' }} Karyawan</h1>
<form method="POST" action="{{ $employee ? route('employees.update', $employee) : route('employees.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf @if($employee) @method('PUT') @endif
    <div class="grid md:grid-cols-3 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">NIP</label><input name="code" value="{{ old('code', $employee?->code) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Nama Lengkap</label><input name="name" value="{{ old('name', $employee?->name) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}" @selected(old('company_id', $employee?->company_id))>{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($sites as $id => $n)<option value="{{ $id }}" @selected(old('site_id', $employee?->site_id))>{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Divisi</label>
            <select name="division_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($divisions as $id => $n)<option value="{{ $id }}" @selected(old('division_id', $employee?->division_id))>{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Departemen</label>
            <select name="department_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($departments as $id => $n)<option value="{{ $id }}" @selected(old('department_id', $employee?->department_id))>{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Posisi</label><input name="position" value="{{ old('position', $employee?->position) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tipe Kepegawaian</label>
            <select name="employment_type" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach (['PERMANENT' => 'Tetap', 'CONTRACT' => 'Kontrak', 'PROBATION' => 'Percobaan', 'DAILY' => 'Harian', 'OUTSOURCED' => 'Outsourced'] as $v => $t)
                <option value="{{ $v }}" @selected(old('employment_type', $employee?->employment_type ?? 'PERMANENT'))>{{ $t }}</option>@endforeach
            </select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Shift</label>
            <select name="shift_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($shifts as $id => $n)<option value="{{ $id }}" @selected(old('shift_id', $employee?->shift_id))>{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Gaji Pokok (Rp)</label><input type="number" step="0.01" name="basic_salary" value="{{ old('basic_salary', $employee?->basic_salary) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">No HP</label><input name="phone" value="{{ old('phone', $employee?->phone) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Bank</label><input name="bank_name" value="{{ old('bank_name', $employee?->bank_name) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">No Rekening</label><input name="bank_account" value="{{ old('bank_account', $employee?->bank_account) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tgl Masuk</label><input type="date" name="join_date" value="{{ old('join_date', $employee?->join_date?->toDateString()) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
            <select name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach (['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'TERMINATED' => 'Berakhir'] as $v => $t)
                <option value="{{ $v }}" @selected(old('status', $employee?->status ?? 'ACTIVE'))>{{ $t }}</option>@endforeach
            </select></div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('employees.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection