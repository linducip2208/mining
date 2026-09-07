@extends('layouts.app')
@section('title', ' - Jenis Surat')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Jenis Surat</h1>
    <a href="{{ route('letters.index') }}" class="text-sm text-amber-600 hover:underline">← Register Surat</a>
</div>
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="font-semibold text-sm mb-3">Tambah Jenis</h2>
    <form method="POST" action="{{ route('letter-types.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        @csrf
        <input name="code" required maxlength="20" placeholder="Kode (SP)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="name" required maxlength="150" placeholder="Nama jenis" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] md:col-span-2">
        <input name="numbering_format" required maxlength="200" value="{SEQ:3}/{TYPE}-{COMPANY}/{MONTH_ROMAN}/{YEAR}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] font-mono">
        <select name="reset_period" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option>YEARLY</option><option>MONTHLY</option><option>NEVER</option></select>
        <div class="md:col-span-5"><button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Simpan</button></div>
    </form>
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th><th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Format Nomor</th><th class="px-4 py-2.5">Reset</th><th class="px-4 py-2.5">Aktif</th>
    </x-slot:head>
    <tbody>
        @foreach ($types as $t)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-mono font-semibold">{{ $t->code }}</td>
            <td class="px-4 py-2.5">{{ $t->name }}</td>
            <td class="px-4 py-2.5 font-mono text-xs">{{ $t->numbering_format }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $t->reset_period }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $t->is_active ? 'Ya' : 'Tidak' }}</td>
        </tr>
        @endforeach
    </tbody>
</x-table>
@endsection
