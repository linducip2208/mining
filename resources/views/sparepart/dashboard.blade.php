@extends('layouts.app')
@section('title', ' - Gudang Sparepart')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Gudang Sparepart</h1>
        <p class="text-sm text-slate-500">Saldo dari stock ledger — bukan tabel terpisah</p>
    </div>
    <form method="GET" class="flex gap-2">
        <select name="warehouse_id" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]" aria-label="Gudang"><option value="">Semua gudang</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected($warehouseId == $w->id)>{{ $w->name }}</option>@endforeach</select>
    </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    @foreach([['Total Sparepart', count($rows)], ['Nilai Stok (Rp)', number_format($value, 0, ',', '.')], ['Stok Kritis', $critical], ['Out of Stock', $outOfStock], ['Masuk Hari Ini', $inToday], ['Keluar Hari Ini', $outToday], ['WO Menunggu Sparepart', $woWaiting], ['Opname Terbuka', $opnameOpen]] as [$label, $v])
    <div class="dashboard-card p-4 min-w-0"><div class="text-xs font-semibold text-slate-500 truncate">{{ $label }}</div><div class="mt-1 text-xl font-bold break-words">{{ $v }}</div></div>
    @endforeach
</div>

<div class="flex flex-wrap gap-2 mb-4">
    @can('sparepart.create')<a href="{{ route('sparepart.master') }}" class="px-4 py-2.5 rounded-lg bg-slate-900 text-white text-sm font-semibold min-h-[44px] inline-flex items-center">Master</a>@endcan
    @can('sparepart_receipt.create')<a href="{{ route('sparepart.receipt') }}" class="px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold min-h-[44px] inline-flex items-center">+ Barang Masuk</a>@endcan
    @can('sparepart_issue.create')<a href="{{ route('sparepart.issue') }}" class="px-4 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px] inline-flex items-center">− Barang Keluar</a>@endcan
    <a href="{{ route('sparepart.card') }}" class="px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-sm min-h-[44px] inline-flex items-center">Kartu Stok</a>
    <a href="{{ route('sparepart.reports') }}" class="px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-sm min-h-[44px] inline-flex items-center">Laporan</a>
    <a href="{{ route('sparepart.recommend') }}" class="px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-sm min-h-[44px] inline-flex items-center">Rekomendasi PR</a>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-2">Sparepart Stok Kritis</h2>
        @forelse(collect($rows)->whereIn('status', ['CRITICAL', 'OUT_OF_STOCK'])->take(8) as $r)
        <div class="flex justify-between gap-2 text-sm py-1.5 border-b border-slate-100"><span class="font-mono">{{ $r['item']->code }}</span><span class="{{ $r['status'] === 'OUT_OF_STOCK' ? 'text-red-600 font-bold' : 'text-amber-600 font-semibold' }}">{{ $r['avail'] }}</span></div>
        @empty<div class="text-sm text-slate-400">Tidak ada stok kritis.</div>@endforelse
    </div>
    <div class="dashboard-card p-5">
        <h2 class="font-semibold mb-2">Top Konsumsi 6 Bulan</h2>
        @forelse($usage as $u)
        <div class="flex justify-between gap-2 text-sm py-1.5 border-b border-slate-100"><span class="font-mono">{{ $u->item?->code }}</span><span>{{ number_format($u->total, 1) }}</span></div>
        @empty<div class="text-sm text-slate-400">Belum ada pemakaian.</div>@endforelse
    </div>
</div>
@endsection
