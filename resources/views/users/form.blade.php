@extends('layouts.app')
@section('title', ' - Pengguna')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">{{ $user ? 'Edit' : 'Tambah' }} Pengguna</h1>
<form method="POST" action="{{ $user ? route('users.update', $user) : route('users.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf @if($user) @method('PUT') @endif
    <div class="grid md:grid-cols-2 gap-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Nama</label>
            <input name="name" value="{{ old('name', $user?->name) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Username</label>
            <input name="username" value="{{ old('username', $user?->username) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Email</label>
            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">No HP</label>
            <input name="phone" value="{{ old('phone', $user?->phone) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        @unless ($user)
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Password</label>
            <input type="password" name="password" required minlength="8" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        @endunless
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Status</label>
            <select name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                @foreach (['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'SUSPENDED' => 'Suspended'] as $v => $t)
                <option value="{{ $v }}" @selected(old('status', $user?->status ?? 'ACTIVE'))>{{ $t }}</option>@endforeach
            </select></div>
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-slate-600 uppercase">Peran</label>
            <div class="mt-2 grid md:grid-cols-3 gap-2">
                @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $user?->roles->pluck('id')->all() ?? []))) class="rounded text-amber-500">
                    {{ $role->name }}
                </label>
                @endforeach
            </div>
        </div>
    </div>
    <div class="flex gap-2 mt-6">
        <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan</button>
        <a href="{{ route('users.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm">Batal</a>
    </div>
</form>
@endsection