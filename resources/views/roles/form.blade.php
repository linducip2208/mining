@extends('layouts.app')

@section('title', ' - Peran')

@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">{{ $role ? 'Edit' : 'Tambah' }} Peran</h1>
<form method="POST" action="{{ $role ? route('role.update', $role) : route('role.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
    @csrf @if($role) @method('PUT') @endif
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Kode</label>
            <input name="code" value="{{ old('code', $role?->code) }}" required maxlength="50" {{ $role?->is_system ? 'readonly' : '' }} class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm uppercase">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Nama Peran</label>
            <input name="name" value="{{ old('name', $role?->name) }}" required maxlength="100" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
        </div>
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-slate-600 uppercase">Deskripsi</label>
            <textarea name="description" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">{{ old('description', $role?->description) }}</textarea>
        </div>
    </div>
    <p class="text-xs text-slate-400 mt-2">Setelah menyimpan, atur izin lewat halaman Matriks Izin.</p>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('role.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection
