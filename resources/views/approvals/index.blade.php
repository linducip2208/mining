@extends('layouts.app')
@section('title', ' - Persetujuan')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Pusat Persetujuan</h1>

<div class="bg-white rounded-xl border border-slate-200 mb-4">
    <div class="px-5 py-3 border-b border-slate-100 font-semibold text-sm">Menunggu Tindakan Saya ({{ $pending->count() }})</div>
    <div class="divide-y divide-slate-100">
        @forelse ($pending as $p)
        <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-sm font-medium">{{ $p->request->module }} · {{ $p->request->transaction_number }}</div>
                <div class="text-xs text-slate-400">Rp {{ number_format($p->request->amount, 0, ',', '.') }} · diajukan {{ $p->request->submitted_at?->diffForHumans() }}</div>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('approval.act', ['action' => 'approve']) }}" method="POST" class="inline">
                    @csrf <input type="hidden" name="approval_action_id" value="{{ $p->id }}">
                    <button class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-xs font-semibold">Setujui</button>
                </form>
                <form action="{{ route('approval.act', ['action' => 'reject']) }}" method="POST" class="inline" onsubmit="const r=prompt('Alasan penolakan:'); if(r){this.notes.value=r}else{return false}">
                    @csrf <input type="hidden" name="approval_action_id" value="{{ $p->id }}"><input type="hidden" name="notes">
                    <button class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold">Tolak</button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-5 py-8 text-center text-slate-400 text-sm">Tidak ada persetujuan menunggu</div>
        @endforelse
    </div>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Modul</th><th class="px-4 py-2.5">Transaksi</th>
        <th class="px-4 py-2.5 text-right">Nilai</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5">Diajukan</th>
    </x-slot:head>
    <tbody>
        @forelse ($requests as $r)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $r->number }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $r->module }}</td>
            <td class="px-4 py-2.5">{{ $r->transaction_number }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($r->amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$r->status" /></td>
            <td class="px-4 py-2.5 text-xs">{{ $r->submitted_at?->format('d/m/Y H:i') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada riwayat</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $requests->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection