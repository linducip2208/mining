@extends('layouts.app')
@section('title', ' - Persetujuan')
@section('content')
<section class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6"><div><div class="section-kicker mb-2">Workflow · risk control</div><h1 class="text-[26px] font-bold tracking-[-.03em] text-slate-900">Approval center</h1><p class="mt-1 text-sm text-slate-500">Tinjau dokumen bernilai tinggi dengan jejak keputusan yang lengkap.</p></div><div class="flex gap-1 p-1 rounded-lg bg-slate-100"><button class="px-3 py-1.5 rounded-md bg-white shadow-sm text-xs font-semibold">My approval</button><button class="px-3 py-1.5 text-xs text-slate-500">Submitted by me</button><button class="px-3 py-1.5 text-xs text-slate-500">Completed</button></div></section>

<x-ui.card title="Menunggu tindakan saya" :subtitle="$pending->count() . ' dokumen dalam antrian Anda'" class="dashboard-card">
    <div class="divide-y divide-slate-100 dark:divide-slate-700/50 -m-5 mt-0 p-0">
        @forelse ($pending as $p)
        @php
            $req = $p->request;
            $age = $req->submitted_at ? now()->diffInDays($req->submitted_at) : 0;
            $prio = $age >= 7 ? ['KRITIS', 'critical'] : ($age >= 3 ? ['TINGGI', 'warning'] : ['NORMAL', 'info']);
            $steps = $req->actions()->orderBy('sequence')->get();
            $cur = $steps->firstWhere('action', 'PENDING');
        @endphp
        <details class="group px-5 py-4 hover:bg-amber-50/30 border-l-2 border-transparent open:border-amber-400">
            <summary class="flex flex-wrap items-center gap-3 cursor-pointer list-none">
                <span class="w-10 h-10 flex-none rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-sm">{{ substr($req->module, 0, 2) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold truncate">{{ $req->module }} · {{ $req->transaction_number }}</span>
                    <span class="block text-xs text-slate-400 mt-0.5">
                        {{ $req->requestedBy?->name ?? '—' }} · Rp {{ number_format($req->amount, 0, ',', '.') }} ·
                        tahap {{ $cur?->sequence ?? '—' }}/{{ $steps->count() }} · {{ $req->submitted_at?->diffForHumans() }}
                    </span>
                </span>
                <x-ui.badge-priority :level="$prio[1]">{{ $prio[0] }}</x-ui.badge-priority>
                <span class="flex gap-2" onclick="event.preventDefault()">
                    <form action="{{ route('approval.act', ['action' => 'approve']) }}" method="POST" class="inline-flex gap-1.5 items-center">
                        @csrf <input type="hidden" name="approval_action_id" value="{{ $p->id }}">
                        <input type="text" name="notes" maxlength="1000" placeholder="Catatan…" aria-label="Catatan persetujuan" class="px-2.5 py-1.5 rounded-ctl border border-slate-200 dark:border-slate-700 bg-white dark:bg-navy-900 text-xs w-36 outline-none focus:border-amber-400">
                        <x-ui.button size="sm" variant="success">Setujui</x-ui.button>
                    </form>
                    <form action="{{ route('approval.act', ['action' => 'reject']) }}" method="POST" class="inline" onsubmit="const r=prompt('Alasan penolakan:'); if(r){this.notes.value=r}else{return false}">
                        @csrf <input type="hidden" name="approval_action_id" value="{{ $p->id }}"><input type="hidden" name="notes">
                        <x-ui.button size="sm" variant="danger">Tolak</x-ui.button>
                    </form>
                </span>
            </summary>
            <div class="mt-3 ml-1">
                <x-ui.timeline :items="$steps->map(fn ($s) => ['title' => 'Tahap ' . $s->sequence . ' — ' . ($s->approver?->name ?? '—'), 'body' => ($s->action === 'PENDING' ? 'Menunggu' : $s->action) . ($s->notes ? ': ' . $s->notes : ''), 'time' => $s->acted_at?->format('d/m/Y H:i'), 'done' => $s->action !== 'PENDING'])->all()" />
            </div>
        </details>
        @empty
        <x-ui.empty-state icon="check-circle" title="Antrian kosong" body="Tidak ada dokumen menunggu tindakan Anda." />
        @endforelse
    </div>
</x-ui.card>

<x-ui.card title="Riwayat Pengajuan" class="mt-4">
    <x-ui.table>
        <x-slot:head>
            <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Modul</th><th class="px-4 py-2.5">Transaksi</th>
            <th class="px-4 py-2.5 text-right">Nilai</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5">Diajukan</th>
        </x-slot:head>
        @forelse ($requests as $r)
        <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
            <td class="px-4 py-2.5 font-mono text-xs">{{ $r->number }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $r->module }}</td>
            <td class="px-4 py-2.5">{{ $r->transaction_number }}</td>
            <td class="px-4 py-2.5 text-right">Rp {{ number_format($r->amount, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$r->status" /></td>
            <td class="px-4 py-2.5 text-xs text-slate-400">{{ $r->submitted_at?->format('d/m/Y H:i') }}</td>
        </tr>
        @empty
        <tr><td colspan="6"><x-ui.empty-state title="Belum ada riwayat" /></td></tr>
        @endforelse
        <x-slot:footer>{{ $requests->links('components.pagination') }}</x-slot:footer>
    </x-ui.table>
</x-ui.card>
@endsection
