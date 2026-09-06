@extends('layouts.app')
@section('title', ' - Keamanan Akun')
@section('content')
<x-ui.page-header title="Keamanan Akun" description="Perbarui password Anda secara berkala — gunakan kombinasi huruf, angka, dan simbol." />
<form method="POST" action="{{ route('password.profile-update') }}" class="bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-6 max-w-md">
    @csrf @method('PUT')
    <div class="space-y-4">
        <x-ui.input name="current_password" label="Password Saat Ini" type="password" required />
        <x-ui.input name="password" label="Password Baru" type="password" required hint="Minimal 8 karakter." />
        <x-ui.input name="password_confirmation" label="Konfirmasi Password Baru" type="password" required />
    </div>
    <x-ui.button class="mt-6">Simpan Password</x-ui.button>
</form>
@endsection
