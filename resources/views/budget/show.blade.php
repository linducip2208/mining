@extends('layouts.app')

@section('title', ' - Budget ' . $budget->number)

@section('content')
<div class="mb-4">
    <a href="{{ route('budgets.index') }}" class="text-xs text-indigo-600 hover:underline">← Kembali ke daftar</a>
    <h1 class="text-xl font-bold text-slate-800 mt-1">Budget {{ $budget->number }} <x-status-badge :status="$budget->status" /></h1>
    <p class="text-sm text-slate-500">{{ $budget->year }} · {{ $budget->type }} · v{{ $budget->version }} · {{ $budget->company?->name }} / {{ $budget->site?->name ?? 'Pusat' }}</p>
</div>

<div class="grid md:grid-cols-4 gap-3 mb-4">
    <x-stat-card title="Pagu" :value="'Rp ' . number_format($report['totals']['budget'] ?? 0, 0)" color="slate" />
    <x-stat-card title="Komitmen (PR/PO)" :value="'Rp ' . number_format($report['totals']['committed'] ?? 0, 0)" color="blue" />
    <x-stat-card title="Aktual (Jurnal)" :value="'Rp ' . number_format($report['totals']['actual'] ?? 0, 0)" color="amber" />
    <x-stat-card title="Sisa" :value="'Rp ' . number_format($report['totals']['available'] ?? 0, 0)" color="green" />
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <x-table>
        <x-slot:head>
            <th class="px-4 py-2.5">COA</th>
            <th class="px-4 py-2.5">Periode</th>
            <th class="px-4 py-2.5 text-right">Pagu</th>
            <th class="px-4 py-2.5 text-right">Komitmen</th>
            <th class="px-4 py-2.5 text-right">Aktual</th>
            <th class="px-4 py-2.5 text-right">Sisa</th>
            <th class="px-4 py-2.5 text-right">Terserap %</th>
        </x-slot:head>
        @forelse ($report['rows'] ?? [] as $r)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-mono text-xs">{{ $r['line']->chartOfAccount?->code }} — {{ $r['line']->chartOfAccount?->name }}</td>
            <td class="px-4 py-2.5">{{ $r['line']->period ?? 'Tahunan' }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['budget'], 0) }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['committed'], 0) }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['actual'], 0) }}</td>
            <td class="px-4 py-2.5 text-right font-semibold {{ $r['available'] < 0 ? 'text-red-600' : '' }}">Rp {{ number_format($r['available'], 0) }}</td>
            <td class="px-4 py-2.5 text-right">{{ $r['used_pct'] }}%</td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada baris</td></tr>
        @endforelse
    </x-table>
</div>

<div class="flex gap-2">
    @can('budget.approve')
    @if (in_array($budget->status, ['DRAFT', 'REVISED']))
    <form method="POST" action="{{ route('budgets.approve', $budget) }}">@csrf<button class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold">Approve</button></form>
    @endif
    @if ($budget->status === 'APPROVED')
    <form method="POST" action="{{ route('budgets.close', $budget) }}" onsubmit="return confirm('Tutup budget ini?')">@csrf<button class="px-5 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold">Tutup</button></form>
    @endif
    @endcan
    @can('budget.revise')
    @if (in_array($budget->status, ['APPROVED', 'REVISED']))
    <form method="POST" action="{{ route('budgets.revise', $budget) }}">@csrf<button class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Buka Revisi</button></form>
    @endif
    @endcan
</div>
@endsection
