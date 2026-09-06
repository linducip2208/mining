@extends('layouts.app')

@section('title', ' - Dashboard HSE')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Dashboard HSE</h1>
    <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }}</p>
</div>

<x-filter-bar :route="route('hse.dashboard')">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<div class="grid md:grid-cols-6 gap-3 mb-4">
    <x-stat-card title="Insiden" :value="$incidents ?? 0" color="red" />
    <x-stat-card title="Near Miss" :value="$near_miss ?? 0" color="amber" />
    <x-stat-card title="Bahaya Dilaporkan" :value="$hazards ?? 0" color="blue" />
    <x-stat-card title="Action Terbuka" :value="$open_actions ?? 0" color="slate" />
    <x-stat-card title="Action Terlambat" :value="$overdue_actions ?? 0" color="red" />
    <x-stat-card title="Hari Tanpa Kecelakaan" :value="$safe_days ?? '—'" color="green" />
</div>

<div class="grid md:grid-cols-2 gap-3 mb-4">
    <a href="{{ route('hse.permits.index') }}" class="rounded-xl border {{ ($permits_expiring ?? 0) > 0 ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200' }} p-4 block">
        <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Permit Kedaluwarsa ≤ 14 hari</div>
        <div class="mt-1 text-2xl font-bold text-slate-800">{{ $permits_expiring ?? 0 }}</div>
    </a>
</div>

<div class="flex gap-2 text-sm">
    <a href="{{ route('hse.reports.index') }}" class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Laporan →</a>
    <a href="{{ route('hse.permits.index') }}" class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Permit Kerja →</a>
    <a href="{{ route('hse.activities.index') }}" class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:border-amber-400">Kegiatan K3 →</a>
</div>
@endsection
