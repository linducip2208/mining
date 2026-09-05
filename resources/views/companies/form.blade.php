@extends('layouts.app')

@section('title', ' - Perusahaan')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $item ? 'Edit' : 'Tambah' }} Perusahaan</h1>
</div>

<form method="POST" action="{{ $item ? route('companies.update', $item) : route('companies.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-4xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
                    <input type="text" name="code" value="{{ $item->code ?? old('code') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
                    <input type="text" name="name" value="{{ $item->name ?? old('name') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">NPWP</label>
                    <input type="text" name="npwp" value="{{ $item->npwp ?? old('npwp') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Alamat</label>
                    <textarea name="address" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">{{ $item->address ?? old('address') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Kota</label>
                    <input type="text" name="city" value="{{ $item->city ?? old('city') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Telepon</label>
                    <input type="text" name="phone" value="{{ $item->phone ?? old('phone') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase">Email</label>
                    <input type="email" name="email" value="{{ $item->email ?? old('email') }}"  class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <label class="flex items-center gap-2 md:col-span-2">
                    <input type="checkbox" name="status" value="1" @checked(old('status', $item->status ?? true)) class="rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                    <span class="text-sm">Aktif</span>
                </label>
    </div>

    <div class="flex gap-2 mt-6">
        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('companies.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection