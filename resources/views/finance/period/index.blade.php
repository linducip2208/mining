@extends('layouts.app')

@section('title', ' - Periode Fiskal')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Periode Fiskal {{ $year }}</h1>
    <p class="text-sm text-slate-500">Tutup periode mengunci jurnal — reopen butuh alasan & audit</p>
</div>

<x-filter-bar :route="route('fiscal-periods.index')">
    <x-filter-input name="year" label="Tahun" placeholder="2026" />
    <x-filter-input name="company_id" label="Perusahaan" type="select" :options="$companies ?? []" />
</x-filter-bar>

@can('fiscal.close')
<div class="grid md:grid-cols-2 gap-4 mb-4">
    <form method="POST" action="{{ route('fiscal-periods.close-year') }}" class="bg-white rounded-xl border border-slate-200 p-4" onsubmit="return confirm('Tutup buku tahun berjalan? Jurnal laba ditahan akan diposting.')">
        @csrf
        <div class="text-sm font-bold text-slate-700 mb-2">Tutup Buku Tahunan</div>
        <div class="grid grid-cols-2 gap-2">
            <select name="company_id" required class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">Perusahaan...</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
            <input type="number" min="2000" max="2100" name="year" value="{{ $year }}" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <button class="mt-2 px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-semibold">Tutup Buku</button>
    </form>
    <form method="POST" action="{{ route('fiscal-periods.depreciate') }}" class="bg-white rounded-xl border border-slate-200 p-4">
        @csrf
        <div class="text-sm font-bold text-slate-700 mb-2">Posting Penyusutan Aset</div>
        <div class="grid grid-cols-2 gap-2">
            <select name="company_id" required class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">Perusahaan...</option>
                @foreach ($companies ?? [] as $id => $n)
                    <option value="{{ $id }}">{{ $n }}</option>
                @endforeach
            </select>
            <input type="month" name="period" value="{{ now()->format('Y-m') }}" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <button class="mt-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">Posting Susut</button>
    </form>
</div>
@endcan

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Perusahaan</th>
        <th class="px-4 py-2.5">Periode</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5">Ditutup Oleh</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->company?->name }}</td>
        <td class="px-4 py-2.5 font-mono">{{ $item->period }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5">{{ $item->closer?->name ?? '—' }}</td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @can('fiscal.close')
            @if ($item->status === 'OPEN')
            <form method="POST" action="{{ route('fiscal-periods.close', $item) }}" class="inline" onsubmit="return confirm('Tutup periode {{ $item->period }}?')">
                @csrf<button class="text-amber-600 hover:underline text-xs">Tutup</button>
            </form>
            @endif
            @endcan
            @can('fiscal.reopen')
            @if ($item->status === 'CLOSED')
            <form method="POST" action="{{ route('fiscal-periods.reopen', $item) }}" class="inline-flex gap-1 items-center" onsubmit="return confirm('Buka kembali periode {{ $item->period }}? Tercatat di audit.')">
                @csrf
                <input type="text" name="reason" required maxlength="500" placeholder="Alasan..." class="px-2 py-1 rounded border border-slate-200 text-xs w-36">
                <button class="text-indigo-600 hover:underline text-xs">Reopen</button>
            </form>
            @endif
            @endcan
        </td>
    </tr>
    @empty
    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada periode tahun ini</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
