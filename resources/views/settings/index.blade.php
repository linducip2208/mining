@extends('layouts.app')
@section('title', ' - Pengaturan')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Pengaturan Sistem</h1>
<form method="POST" action="{{ route('setting.update') }}">
    @csrf
    @foreach ($settings as $group => $items)
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
        <h3 class="font-semibold text-sm uppercase text-slate-500 mb-3">{{ $group }}</h3>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach ($items as $setting)
            <div>
                <label class="text-xs font-semibold text-slate-600">{{ $setting->key }}</label>
                <input name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
    @can('setting.update')
    <button class="px-5 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold">Simpan Pengaturan</button>
    @endcan
</form>
@endsection