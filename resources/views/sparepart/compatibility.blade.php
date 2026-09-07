@extends('layouts.app')
@section('title', ' - Kompatibilitas Sparepart')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-1">Kompatibilitas Sparepart ↔ Equipment</h1>
<p class="text-sm text-slate-500 mb-4">Opsional — tidak wajib untuk semua barang.</p>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <form method="POST" action="{{ route('sparepart.compatibility.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        @csrf
        <select name="item_id" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Sparepart --</option>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->code }}</option>@endforeach</select>
        <input name="equipment_make" maxlength="100" placeholder="Make (opsional)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="equipment_model" maxlength="100" placeholder="Model (opsional)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="part_number" maxlength="100" placeholder="Part number" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="note" maxlength="255" placeholder="Catatan" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <div class="md:col-span-5"><button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Tambah</button></div>
    </form>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Sparepart</th><th class="px-4 py-2.5">Make</th><th class="px-4 py-2.5">Model</th><th class="px-4 py-2.5">Part No</th><th class="px-4 py-2.5">Catatan</th>
    </x-slot:head>
    <tbody>
        @forelse ($rows as $r)
        <tr class="hover:bg-slate-50"><td class="px-4 py-2.5 font-mono text-xs">{{ $r->item?->code }}</td><td class="px-4 py-2.5">{{ $r->equipment_make }}</td><td class="px-4 py-2.5">{{ $r->equipment_model }}</td><td class="px-4 py-2.5 font-mono text-xs">{{ $r->part_number }}</td><td class="px-4 py-2.5 text-xs">{{ $r->note }}</td></tr>
        @empty
        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada mapping</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $rows->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
