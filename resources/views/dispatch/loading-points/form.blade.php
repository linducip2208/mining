@extends('layouts.app')

@section('title', ' - Titik Muat')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Titik Muat</h1>
</div>

<form method="POST" action="{{ $item ? route('loading-points.update', $item) : route('loading-points.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? \App\Models\Site::orderBy('name')->get() as $s)
                    <option value="{{ $s->id ?? $s }}" @selected(($item->site_id ?? old('site_id')) == ($s->id ?? $s))>{{ $s->name ?? $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Pit</label>
            <select name="pit_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="">-- Pilih --</option>
                @foreach (\App\Models\Pit::orderBy('name')->get() as $p)
                    <option value="{{ $p->id }}" @selected(($item->pit_id ?? old('pit_id')) == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required maxlength="30" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required maxlength="150" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
            <select name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:border-amber-400 outline-none">
                <option value="1" @selected(($item->status ?? old('status', 1)) == 1)>Aktif</option>
                <option value="0" @selected(($item->status ?? old('status')) == 0)>Nonaktif</option>
            </select>
        </div>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('loading-points.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
