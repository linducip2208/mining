@extends('layouts.app')
@section('title', ' - Dokumen')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Tambah Dokumen / Surat</h1>
<form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Kategori</label>
            <select name="category" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($categories as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Divisi (untuk nomor surat)</label>
            <select name="division_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">-- Umum --</option>@foreach ($divisions as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Subjek</label>
            <input name="subject" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Tanggal</label>
            <input type="date" name="date" value="{{ today()->toDateString() }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Kadaluarsa (opsional)</label>
            <input type="date" name="expiry_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Lampiran (PDF/DOC/IMG, max 10MB)</label>
            <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" class="mt-1 w-full text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Isi / Ringkasan</label>
            <textarea name="body" rows="4" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></textarea></div>
    </div>
    <p class="text-xs text-slate-400 mt-2">Nomor surat dibuat otomatis: 001/DIVI/I/2026</p>
    <div class="flex gap-2 mt-4">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('documents.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection