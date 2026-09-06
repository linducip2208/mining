@extends('layouts.app')

@section('title', ' - Biaya Manual')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Biaya Manual</h1>
    <p class="text-sm text-slate-500">Kontraktor, royalti, overhead, hauling, crusher · <a href="{{ route('cost.dashboard') }}" class="text-indigo-600 hover:underline">← Dashboard biaya</a></p>
</div>

@can('cost.create')
<form method="POST" action="{{ route('cost.others.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="grid md:grid-cols-4 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Site</label>
            <select name="site_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($sites ?? [] as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Pit</label>
            <select name="pit_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach ($pits ?? [] as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Periode (YYYY-MM)</label>
            <input type="month" name="period" value="{{ now()->format('Y-m') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Komponen</label>
            <select name="component" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                @foreach ($components ?? [] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Jumlah (Rp)</label>
            <input type="number" step="0.01" min="0.01" name="amount" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-slate-600 uppercase">Deskripsi</label>
            <input type="text" name="description" maxlength="255" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Draft</button>
</form>
@endcan

<x-filter-bar :route="route('cost.others.index')">
    <x-filter-input name="period" label="Periode" type="month" />
    <x-filter-input name="status" label="Status" type="select" :options="['DRAFT' => 'Draft', 'APPROVED' => 'Disetujui', 'POSTED' => 'Posted']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Periode</th>
        <th class="px-4 py-2.5">Komponen</th>
        <th class="px-4 py-2.5">Site / Pit</th>
        <th class="px-4 py-2.5">Deskripsi</th>
        <th class="px-4 py-2.5 text-right">Jumlah</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5 font-mono">{{ $item->period }}</td>
        <td class="px-4 py-2.5">{{ $item->component }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->site?->name ?? '—' }} / {{ $item->pit?->name ?? '—' }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->description }}</td>
        <td class="px-4 py-2.5 text-right">Rp {{ number_format($item->amount, 0) }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @can('cost.create')
            @if ($item->status === 'DRAFT')
            <form method="POST" action="{{ route('cost.others.approve', $item) }}" class="inline">@csrf<button class="text-green-600 hover:underline text-xs">Ajukan</button></form>
            @endif
            @endcan
            @can('cost.post')
            @if ($item->status === 'APPROVED')
            <form method="POST" action="{{ route('cost.others.post', $item) }}" class="inline ml-2" onsubmit="return confirm('Posting ke jurnal?')">@csrf<button class="text-amber-600 hover:underline text-xs">Posting</button></form>
            @endif
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
