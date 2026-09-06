@extends('layouts.app')

@section('title', ' - ' . ($title ?? 'Utilisasi Armada'))

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">{{ $title ?? 'Utilisasi Armada' }}</h1>
        <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }} · <a href="{{ route('fleet.dashboard') }}" class="text-indigo-600 hover:underline">Kembali ke dashboard</a></p>
    </div>
</div>

<x-filter-bar :route="url()->current()">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Unit</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Tersedia (H)</th>
        <th class="px-4 py-2.5 text-right">Operasi (H)</th>
        <th class="px-4 py-2.5 text-right">Idle (H)</th>
        <th class="px-4 py-2.5 text-right">Util %</th>
        <th class="px-4 py-2.5">Utilisasi</th>
    </x-slot:head>
    @forelse ($rows ?? [] as $r)
    @php $u = $r['kpi']['utilization_pct']; @endphp
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5"><span class="font-mono font-semibold">{{ $r['unit']->code }}</span> <span class="text-slate-500">{{ $r['unit']->name }}</span></td>
        <td class="px-4 py-2.5"><x-status-badge :status="$r['unit']->status" /></td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['available_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['operating_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right">{{ number_format($r['kpi']['idle_hours'], 1) }}</td>
        <td class="px-4 py-2.5 text-right font-semibold">{{ $u }}%</td>
        <td class="px-4 py-2.5">
            <div class="w-40 h-2 rounded bg-slate-100 overflow-hidden"><div class="h-full {{ $u >= 70 ? 'bg-green-500' : ($u >= 40 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($u, 100) }}%"></div></div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada unit pada filter ini</td></tr>
    @endforelse
</x-table>
@endsection
