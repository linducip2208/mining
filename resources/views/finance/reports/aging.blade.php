@extends('layouts.app')
@section('title', ' - Aging')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">{{ $title }}</h1>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
    @foreach ($buckets as $key => $bucket)
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-[11px] uppercase text-slate-400 font-semibold">{{ $bucket['label'] }}</div>
        <div class="text-lg font-bold text-slate-700">Rp {{ number_format($bucket['total'], 0, ',', '.') }}</div>
    </div>
    @endforeach
</div>
<div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
    @foreach ($buckets as $key => $bucket)
    @foreach ($bucket['items'] as $item)
    <div class="px-5 py-2.5 flex justify-between text-sm">
        <div>
            <span class="font-medium">{{ $item->number }}</span>
            <span class="text-slate-400 ml-2">{{ ($side === 'AR' ? $item->customer?->name : $item->supplier?->name) }}</span>
        </div>
        <div class="text-right">
            <span class="font-semibold">Rp {{ number_format($item->total - $item->paid_amount, 0, ',', '.') }}</span>
            <span class="text-xs text-slate-400 ml-2">jatuh tempo {{ ($item->due_date ?? $item->invoice_date ?? $item->bill_date)?->format('d/m/Y') }}</span>
        </div>
    </div>
    @endforeach
    @endforeach
</div>
@endsection