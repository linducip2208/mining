@extends('layouts.app')
@section('title', ' - CSR')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Program CSR</h1>
    @can('csr.create')<x-btn-create :href="route('csr.create')" />@endcan
</div>

@if ($program)
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex justify-between">
        <div><span class="font-bold">{{ $program->number }}</span> · {{ $program->name }}</div>
        <x-status-badge :status="$program->status" />
    </div>
    <div class="grid grid-cols-3 gap-3 mt-3">
        <x-stat-card title="Anggaran" :value="'Rp ' . number_format($program->budget, 0, ',', '.')" color="blue" />
        <x-stat-card title="Realisasi" :value="'Rp ' . number_format($program->activities->sum('actual_cost'), 0, ',', '.')" color="green" />
        <x-stat-card title="Aktivitas" :value="$program->activities->count()" color="slate" />
    </div>
    @if ($program->status === 'PROPOSAL')
    @can('csr.approve')
    <form action="{{ route('csr.approve', $program) }}" method="POST" class="mt-3">@csrf
        <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm">Setujui Program</button></form>
    @endcan
    @endif
    <div class="mt-4 grid md:grid-cols-2 gap-4">
        <div>
            <h4 class="font-semibold text-sm mb-2">Aktivitas</h4>
            @forelse ($program->activities as $act)
            <div class="flex justify-between text-sm py-1.5 border-b border-slate-100">
                <span>{{ $act->name }} <span class="text-xs text-slate-400">{{ $act->date?->format('d/m/Y') }}</span></span>
                <span>Rp {{ number_format($act->actual_cost, 0, ',', '.') }}</span>
            </div>
            @empty <p class="text-xs text-slate-400">-</p> @endforelse
        </div>
        <div>
            <h4 class="font-semibold text-sm mb-2">Dokumentasi</h4>
            @forelse ($program->documents as $doc)
            <div class="text-sm py-1 border-b border-slate-100">📄 {{ $doc->title }}</div>
            @empty <p class="text-xs text-slate-400">-</p> @endforelse
        </div>
    </div>
</div>
@endif

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Program</th><th class="px-4 py-2.5">Site</th>
        <th class="px-4 py-2.5 text-right">Anggaran</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ Str::limit($item->name, 40) }}</td>
            <td class="px-4 py-2.5">{{ $item->site?->name ?? '-' }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->budget, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5"><a href="{{ route('csr.show', $item) }}" class="text-amber-600 text-xs hover:underline">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada program CSR</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection