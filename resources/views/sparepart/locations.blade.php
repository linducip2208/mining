@extends('layouts.app')
@section('title', ' - Lokasi Penyimpanan')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-1">Lokasi Penyimpanan</h1>
<p class="text-sm text-slate-500 mb-4">Warehouse → Zone → Rack → Bin (contoh: Gudang Sparepart → A → A01 → A01-01)</p>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <form method="POST" action="{{ route('sparepart.locations') }}" class="grid grid-cols-2 md:grid-cols-6 gap-3">
        @csrf
        <select name="warehouse_id" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] md:col-span-2"><option value="">Gudang --</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
        <input name="zone" required maxlength="20" placeholder="Zone (A)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="rack" required maxlength="20" placeholder="Rack (A01)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="bin" required maxlength="20" placeholder="Bin (A01-01)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="name" maxlength="150" placeholder="Nama (opsional)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <div class="col-span-2 md:col-span-6"><button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Simpan Lokasi</button></div>
    </form>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th><th class="px-4 py-2.5">Gudang</th><th class="px-4 py-2.5">Zone/Rack/Bin</th><th class="px-4 py-2.5">Nama</th>
    </x-slot:head>
    <tbody>
        @forelse ($rows as $r)
        <tr class="hover:bg-slate-50"><td class="px-4 py-2.5 font-mono font-semibold">{{ $r->code }}</td><td class="px-4 py-2.5">{{ $r->warehouse?->name }}</td><td class="px-4 py-2.5 text-xs">{{ $r->zone }} / {{ $r->rack }} / {{ $r->bin }}</td><td class="px-4 py-2.5">{{ $r->name }}</td></tr>
        @empty
        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada lokasi</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $rows->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
