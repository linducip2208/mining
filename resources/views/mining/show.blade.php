@extends('layouts.app')
@section('title', ' - Detail Aktivitas')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">{{ $activity->number }}</h1>
        <x-status-badge :status="$activity->status" class="mt-1" />
    </div>
    <div class="flex gap-2">
        @if ($activity->status === 'DRAFT')
        <form action="{{ route('mining.submit', $activity) }}" method="POST">@csrf
            <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Ajukan</button></form>
        @endif
        @if ($activity->status === 'SUBMITTED')
        @can('mining.approve')
        <form action="{{ route('mining.approve', $activity) }}" method="POST">@csrf
            <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm">Setujui</button></form>
        @endcan
        @endif
        @if ($activity->status === 'APPROVED')
        @can('mining.post')
        <form action="{{ route('mining.post', $activity) }}" method="POST">@csrf
            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm">Posting ke Stok</button></form>
        @endcan
        @endif
    </div>
</div>
<div class="grid md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-600 text-sm mb-3">Informasi</h3>
        <dl class="text-sm space-y-1.5">
            <div class="flex justify-between"><dt class="text-slate-400">Tanggal</dt><dd>{{ $activity->date?->format('d/m/Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Site</dt><dd>{{ $activity->site?->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Pit</dt><dd>{{ $activity->pit?->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Shift</dt><dd>{{ $activity->shift?->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Peralatan</dt><dd>{{ $activity->equipment?->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Operator</dt><dd>{{ $activity->operator?->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Jam Kerja</dt><dd>{{ $activity->working_hours }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-600 text-sm mb-3">Material</h3>
        <dl class="text-sm space-y-1.5">
            <div class="flex justify-between"><dt class="text-slate-400">Material</dt><dd>{{ $activity->item?->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Trip</dt><dd>{{ $activity->quantity }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Tonase</dt><dd class="font-bold">{{ number_format($activity->tonnage, 2) }} Ton</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Stockpile</dt><dd>{{ $activity->target_warehouse_id ? \App\Models\Warehouse::find($activity->target_warehouse_id)?->name : '-' }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-600 text-sm mb-3">Hauling ({{ $activity->haulings->count() }})</h3>
        @forelse ($activity->haulings as $h)
        <div class="text-sm flex justify-between py-1 border-b border-slate-100">
            <span>{{ $h->vehicle?->plate_no ?? '-' }}</span><span>{{ number_format($h->tonnage, 2) }} Ton</span>
        </div>
        @empty
        <p class="text-sm text-slate-400">Tidak ada data hauling</p>
        @endforelse
    </div>
</div>
@endsection