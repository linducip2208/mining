@extends('layouts.app')
@section('title', ' - Detail Batch')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">{{ $batch->number }}</h1>
        <x-status-badge :status="$batch->status" class="mt-1" />
    </div>
    <div class="flex gap-2">
        @if ($batch->status === 'DRAFT')
        <form action="{{ route('production.submit', $batch) }}" method="POST">@csrf <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Ajukan</button></form>
        @endif
        @if ($batch->status === 'SUBMITTED')
        @can('production.approve')
        <form action="{{ route('production.approve', $batch) }}" method="POST">@csrf <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm">Setujui</button></form>
        @endcan
        @endif
        @if ($batch->status === 'APPROVED')
        @can('production.post')
        <form action="{{ route('production.post', $batch) }}" method="POST">@csrf <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm">Posting (Stok + Jurnal)</button></form>
        @endcan
        @endif
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
    <x-stat-card title="Input" :value="number_format($batch->input_tonnage, 2) . ' T'" color="slate" />
    <x-stat-card title="Gross Output" :value="number_format($batch->gross_output, 2) . ' T'" color="blue" />
    <x-stat-card title="Loss" :value="number_format($batch->total_loss, 2) . ' T'" color="amber" />
    <x-stat-card title="Scrap" :value="number_format($batch->total_scrap, 2) . ' T'" color="red" />
    <x-stat-card title="Net Output" :value="number_format($batch->net_output, 2) . ' T'" color="green" />
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Input Material</h3>
        @forelse ($batch->inputs as $in)
        <div class="flex justify-between py-1.5 border-b border-slate-100 text-sm"><span>{{ $in->item?->name }}</span><span>{{ number_format($in->tonnage, 2) }} T</span></div>
        @empty <p class="text-sm text-slate-400">-</p> @endforelse
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Output</h3>
        @forelse ($batch->outputs as $out)
        <div class="flex justify-between py-1.5 border-b border-slate-100 text-sm"><span>{{ $out->item?->name }}</span><span>Gross {{ number_format($out->gross_tonnage, 2) }} / Net {{ number_format($out->net_tonnage, 2) }} T</span></div>
        @empty <p class="text-sm text-slate-400">-</p> @endforelse
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-sm mb-3">Loss / Scrap</h3>
        @forelse ($batch->losses as $loss)
        <div class="flex justify-between py-1.5 border-b border-slate-100 text-sm"><span class="text-orange-600">{{ $loss->category }}</span><span>{{ number_format($loss->tonnage, 2) }} T</span></div>
        @empty <p class="text-sm text-slate-400">-</p> @endforelse
        @foreach ($batch->scraps as $scrap)
        <div class="flex justify-between py-1.5 border-b border-slate-100 text-sm"><span class="text-red-500">SCRAP {{ $scrap->item?->name }}</span><span>{{ number_format($scrap->tonnage, 2) }} T</span></div>
        @endforeach
    </div>
</div>
@endsection