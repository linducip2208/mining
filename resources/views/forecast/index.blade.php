@extends('layouts.app')

@section('title', ' - Forecast & Anomali')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Forecast & Deteksi Anomali</h1>
    <p class="text-sm text-slate-500">Statistik internal (moving average + z-score) — tanpa layanan eksternal</p>
</div>

<div class="grid md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-1">Forecast Produksi 7 Hari</div>
        <div class="text-[11px] text-slate-400 mb-3">Tren: {{ $production['trend'] ?? '—' }}</div>
        <div class="text-3xl font-bold text-slate-800">{{ number_format($production['forecast_next_7d'] ?? 0, 1) }} <span class="text-sm font-normal text-slate-500">T</span></div>
        <div class="text-xs text-slate-500 mt-2">Rata-rata harian: {{ number_format($production['daily_avg'] ?? 0, 1) }} T/hari</div>
        <div class="flex items-end gap-1 h-20 mt-3">
            @foreach (($production['history'] ?? []) as $d => $v)
            <div class="flex-1 rounded-t bg-green-400" style="height: {{ min(100, max(5, $v / max(1, max($production['history'] ?? [1])) * 100)) }}%" title="{{ $d }}: {{ number_format($v, 1) }}"></div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-1">Proyeksi Kas 14 Hari</div>
        <div class="text-[11px] text-slate-400 mb-3">Masuk: {{ $cash['trend_in'] ?? '—' }} · Keluar: {{ $cash['trend_out'] ?? '—' }}</div>
        <div class="text-3xl font-bold {{ ($cash['projected_net_14d'] ?? 0) < 0 ? 'text-red-600' : 'text-slate-800' }}">Rp {{ number_format($cash['projected_net_14d'] ?? 0, 0) }}</div>
        <div class="text-xs text-slate-500 mt-2">Rata-rata masuk Rp {{ number_format($cash['avg_daily_in'] ?? 0, 0) }}/hari · keluar Rp {{ number_format($cash['avg_daily_out'] ?? 0, 0) }}/hari</div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-1">Cara Baca</div>
        <ul class="text-xs text-slate-500 space-y-1.5 list-disc pl-4">
            <li>Forecast = moving average 7 hari × 7 (produksi) / 14 (kas).</li>
            <li>Anomali = titik menyimpang &gt; 2,5 sigma dari rata-rata 30/90 hari.</li>
            <li>Klik angka pada kartu anomali untuk menelusur dokumen sumber.</li>
        </ul>
    </div>
</div>

@php
$groups = [
    'Anomali BBM (L/H)' => $fuel ?? [],
    'Anomali Timbangan' => $weighbridge ?? [],
    'Anomali Harga Beli' => $purchase ?? [],
    'Anomali Lembur' => $overtime ?? [],
    'Anomali Downtime' => $downtime ?? [],
    'Anomali Penyesuaian Stok' => $adjustments ?? [],
];
@endphp
<div class="grid md:grid-cols-3 gap-4 mt-4">
    @foreach ($groups as $label => $rows)
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-bold text-slate-700 mb-2">{{ $label }} <span class="ml-1 text-[11px] px-2 py-0.5 rounded-full {{ count($rows) ? 'bg-red-100 text-red-700' : 'bg-green-50 text-green-700' }}">{{ count($rows) }}</span></div>
        @forelse (array_slice($rows, 0, 8) as $r)
        <div class="text-xs text-slate-600 border-t border-slate-100 py-1.5 flex justify-between gap-2">
            <span>{{ is_array($r) ? ($r['label'] ?? '-') : $r }}</span>
            @if (is_array($r) && isset($r['value']))<span class="font-mono font-semibold">{{ $r['value'] }}</span>@endif
        </div>
        @empty
        <div class="text-xs text-emerald-600 py-2">Normal — tidak ada anomali.</div>
        @endforelse
    </div>
    @endforeach
</div>
@endsection
