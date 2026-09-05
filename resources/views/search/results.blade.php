@extends('layouts.app')
@section('title', ' - Pencarian')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Hasil Pencarian: "{{ $q }}"</h1>
@if (strlen($q) < 2)
<p class="text-slate-400">Ketik minimal 2 karakter.</p>
@else
@forelse ($results as $group)
<div class="bg-white rounded-xl border border-slate-200 mb-4">
    <div class="px-5 py-3 border-b border-slate-100 font-semibold text-sm">{{ $group['icon'] }} {{ $group['module'] }} ({{ $group['items']->count() }})</div>
    <div class="divide-y divide-slate-100">
        @foreach ($group['items'] as $item)
        <div class="px-5 py-2.5 flex items-center justify-between text-sm">
            <span class="font-medium">{{ $item->number ?? $item->name }}</span>
            <x-status-badge :status="$item->status ?? 'DRAFT'" />
        </div>
        @endforeach
    </div>
</div>
@empty
<p class="text-slate-400">Tidak ditemukan hasil untuk "{{ $q }}".</p>
@endforelse
@endif
@endsection