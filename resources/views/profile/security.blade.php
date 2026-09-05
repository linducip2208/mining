@extends('layouts.app')
@section('title', ' - Ganti Password')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Ganti Password</h1>
<form method="POST" action="{{ route('password.update') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-md">
    @csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Password Saat Ini</label>
            <input type="password" name="current_password" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Password Baru</label>
            <input type="password" name="password" required minlength="8" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
    </div>
    <button class="mt-6 px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan Password</button>
</form>
@endsection