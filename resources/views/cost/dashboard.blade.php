@extends('layouts.app')

@section('title', ' - Biaya Tambang')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Biaya Tambang (Cost per Ton)</h1>
    <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }} · <a href="{{ route('cost.others.index') }}" class="text-indigo-600 hover:underline">Biaya manual →</a></p>
</div>

<x-filter-bar :route="route('cost.dashboard')">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
    <x-filter-input name="company_id" label="Perusahaan" type="select" :options="$companies ?? []" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
</x-filter-bar>

<div class="grid md:grid-cols-5 gap-3 mb-4">
    <x-stat-card title="Ton Terjual" :value="number_format($saleable_ton ?? 0, 1) . ' T'" color="slate" />
    <x-stat-card title="Total Biaya" :value="'Rp ' . number_format($total_cost ?? 0, 0)" color="red" />
    <x-stat-card title="Biaya / Ton" :value="'Rp ' . number_format($cost_per_ton ?? 0, 0)" color="amber" />
    <x-stat-card title="Pendapatan" :value="'Rp ' . number_format($revenue ?? 0, 0)" color="green" />
    <x-stat-card title="Margin Total" :value="'Rp ' . number_format($margin_total ?? 0, 0)" color="blue" />
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-sm font-bold text-slate-700 mb-3">Komponen Biaya</div>
    <x-table>
        <x-slot:head>
            <th class="px-4 py-2.5">Komponen</th>
            <th class="px-4 py-2.5 text-right">Jumlah</th>
            <th class="px-4 py-2.5 text-right">Rp / Ton</th>
            <th class="px-4 py-2.5 text-right">Share</th>
        </x-slot:head>
        @forelse ($components ?? [] as $c)
        <tr class="hover:bg-slate-50 {{ ($c['info_only'] ?? false) ? 'text-slate-400 italic' : '' }}">
            <td class="px-4 py-2.5">{{ $c['label'] }}@if (!empty($c['note'])) <span class="text-[11px] text-slate-400">({{ $c['note'] }})</span>@endif</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($c['amount'], 0) }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format(($saleable_ton ?? 0) > 0 ? $c['amount'] / $saleable_ton : 0, 0) }}</td>
            <td class="px-4 py-2.5 text-right">{{ ($total_cost ?? 0) > 0 ? round($c['amount'] / $total_cost * 100, 1) : 0 }}%</td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada komponen</td></tr>
        @endforelse
    </x-table>
</div>
@endsection
