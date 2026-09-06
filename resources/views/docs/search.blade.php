@extends('layouts.docs')

@section('meta-title', 'Cari: ' . $q)

@section('breadcrumb')
<span class="mx-1">/</span><span class="text-slate-700 font-medium">Pencarian</span>
@endsection

@section('content')
<h1 class="text-2xl font-bold text-slate-800">Hasil pencarian</h1>
<p class="text-sm text-slate-500 mt-1">Kata kunci: "<strong>{{ $q }}</strong>" — {{ count($hits) }} hasil</p>

@if (mb_strlen(trim($q)) < 2)
<p class="text-sm text-slate-400 mt-4">Ketik minimal 2 karakter.</p>
@elseif (empty($hits))
<p class="text-sm text-slate-400 mt-4">Tidak ditemukan. Coba kata lain, mis. "timbangan", "invoice", "jurnal", "cuti".</p>
@else
<div class="space-y-2 mt-4">
    @foreach ($hits as ['page' => $p])
    <a href="{{ $p['url'] }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:border-amber-400">
        <div class="text-sm font-semibold">{{ $p['title'] }}</div>
        <div class="text-xs text-slate-400 mt-0.5">{{ $p['module'] ?? '' }}</div>
        <div class="text-[13px] text-slate-600 mt-1">{{ \Illuminate\Support\Str::limit($p['purpose'] ?? '', 140) }}</div>
    </a>
    @endforeach
</div>
@endif
@endsection
