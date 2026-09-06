@extends('layouts.app')

@section('title', ' - Tambah Compliance')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Tambah Compliance Register</h1>
</div>

<form method="POST" action="{{ route('compliance.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tipe</label>
            <select name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                @foreach ($types ?? [] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-slate-600 uppercase">Judul</label>
            <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">No Dokumen</label>
            <input type="text" name="document_number" value="{{ old('document_number') }}" maxlength="100" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
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
            <label class="text-xs font-semibold text-slate-600 uppercase">Penanggung Jawab</label>
            <select name="responsible_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($employees ?? [] as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tgl Terbit</label>
            <input type="date" name="issued_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Tgl Berlaku</label>
            <input type="date" name="effective_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kedaluwarsa</label>
            <input type="date" name="expiry_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Lampiran</label>
            <input type="file" name="attachment" class="mt-1 w-full text-sm">
        </div>
        <div class="md:col-span-3">
            <label class="text-xs font-semibold text-slate-600 uppercase">Catatan</label>
            <textarea name="notes" rows="2" maxlength="2000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('compliance.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
