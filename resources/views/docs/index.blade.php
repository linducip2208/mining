@extends('layouts.docs')

@section('meta-title', 'Pusat Dokumentasi')
@section('meta-desc', 'Pusat dokumentasi dan tutorial resmi Mining ERP: panduan seluruh modul, alur end-to-end, FAQ, dan troubleshooting.')

@section('content')
<h1 class="text-2xl font-bold text-slate-800">Pusat Dokumentasi Mining ERP</h1>
<p class="text-sm text-slate-500 mt-1">Tutorial resmi seluruh fitur dengan screenshot nyata dari aplikasi. Gunakan pencarian atau telusuri per modul di sidebar.</p>

<form action="{{ route('docs.search') }}" method="GET" class="mt-4 flex gap-2 max-w-xl">
    <input type="search" name="q" placeholder='Coba: "timbangan", "buat user", "invoice", "jurnal"…'
           class="flex-1 px-4 py-2.5 rounded-lg border border-slate-200 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
    <button class="px-5 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Cari</button>
</form>

@php
    $shortcuts = [
        ['Mulai Cepat', 'Login → Dashboard → transaksi pertama', '/docs/getting-started/login', '🚀'],
        ['Mine to Cash', 'Tambang → timbang → produksi → jual → kas', '/docs/workflows/mine-to-cash', '⛏️'],
        ['Procure to Pay', 'PR → PO → GRN → tagihan → bayar', '/docs/workflows/procure-to-pay', '🧾'],
        ['Butuh bantuan?', 'FAQ & troubleshooting', '/docs/faq/general', '❓'],
    ];
@endphp
<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
    @foreach ($shortcuts as [$t, $d, $u, $e])
    <a href="{{ $u }}" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-amber-400 hover:shadow-sm">
        <div class="text-2xl">{{ $e }}</div>
        <div class="font-semibold text-sm mt-1">{{ $t }}</div>
        <div class="text-xs text-slate-500 mt-0.5">{{ $d }}</div>
    </a>
    @endforeach
</div>

<div class="grid md:grid-cols-2 gap-4 mt-6">
    @foreach ($sections as $sSlug => $s)
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800">{{ $s['title'] }}</h2>
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($s['pages'] as $pSlug => $p)
            <a href="/docs/{{ $sSlug }}/{{ $pSlug }}" class="text-xs px-2.5 py-1 rounded-full bg-slate-100 hover:bg-amber-100 hover:text-amber-800">{{ $p['title'] }}</a>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
