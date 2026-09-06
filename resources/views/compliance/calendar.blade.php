@extends('layouts.app')

@section('title', ' - Kalender Compliance')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Kalender Compliance</h1>
    <p class="text-sm text-slate-500">{{ $from }} s.d. {{ $to }} · <a href="{{ route('compliance.index') }}" class="text-indigo-600 hover:underline">← Register</a></p>
</div>

<x-filter-bar :route="route('compliance.calendar')">
    <x-filter-input name="from" label="Dari" type="date" />
    <x-filter-input name="to" label="Sampai" type="date" />
    <x-filter-input name="company_id" label="Perusahaan" type="select" :options="$companies ?? []" />
    <x-filter-input name="site_id" label="Site" type="select" :options="$sites ?? []" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kedaluwarsa</th>
        <th class="px-4 py-2.5">Sisa Hari</th>
        <th class="px-4 py-2.5">Judul</th>
        <th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5">Penanggung Jawab</th>
        <th class="px-4 py-2.5">Status</th>
    </x-slot:head>
    @forelse ($rows ?? [] as $r)
    @php
        $reg = $r['register'] ?? $r;
        $days = $r['days_left'] ?? null;
    @endphp
    <tr class="hover:bg-slate-50 {{ ($days !== null && $days < 0) ? 'bg-red-50' : '' }}">
        <td class="px-4 py-2.5 font-mono">{{ is_object($reg) ? ($reg->expiry_date ?? '—') : ($reg['expiry_date'] ?? '—') }}</td>
        <td class="px-4 py-2.5 font-semibold {{ ($days !== null && $days <= 30) ? 'text-red-600' : '' }}">{{ $days !== null ? ($days < 0 ? 'Lewat ' . abs($days) . ' hari' : $days . ' hari') : '—' }}</td>
        <td class="px-4 py-2.5">{{ is_object($reg) ? $reg->title : ($reg['title'] ?? '—') }}</td>
        <td class="px-4 py-2.5">{{ is_object($reg) ? $reg->type : ($reg['type'] ?? '—') }}</td>
        <td class="px-4 py-2.5">{{ is_object($reg) ? ($reg->responsible?->name ?? '—') : ($reg['responsible'] ?? '—') }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="is_object($reg) ? $reg->status : ($reg['status'] ?? 'ACTIVE')" /></td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Tidak ada jatuh tempo pada rentang ini</td></tr>
    @endforelse
</x-table>
@endsection
