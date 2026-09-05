@extends('layouts.app')
@section('title', ' - CSR')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Proposal CSR Baru</h1>
<form method="POST" action="{{ route('csr.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    @csrf
    <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">@foreach ($companies as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm"><option value="">--</option>@foreach ($sites as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Nama Program</label>
            <input name="name" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Anggaran (Rp)</label>
            <input type="number" step="0.01" name="budget" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Mulai</label>
            <input type="date" name="start_date" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-600 uppercase">Deskripsi</label>
            <textarea name="description" rows="3" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></textarea></div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan Proposal</button>
        <a href="{{ route('csr.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection