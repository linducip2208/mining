@extends('layouts.app')
@section('title', ' - Tiket Timbangan')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">{{ $ticket->ticket_no }}</h1>
        <x-status-badge :status="$ticket->status" class="mt-1" />
    </div>
    <div class="flex gap-2">
        @if ($ticket->status !== 'FIRST_WEIGH' && $ticket->status !== 'VOID' && $ticket->status !== 'CANCELLED')
        <a href="{{ route('weighbridge.print', $ticket) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm">Cetak Tiket</a>
        @endif
        @if ($ticket->status === 'COMPLETE')
        @can('weighbridge.post')
        <form action="{{ route('weighbridge.post', $ticket) }}" method="POST">@csrf
            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm">Posting</button></form>
        @endcan
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-600 text-sm mb-3">Data Penimbangan</h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="p-3 rounded-lg bg-slate-50 text-center">
                <div class="text-xs text-slate-400">Timbang 1</div>
                <div class="text-xl font-bold font-mono">{{ number_format($ticket->first_weight, 0) }}</div>
                <div class="text-[10px] text-slate-400">{{ $ticket->first_weigh_at?->format('d/m/Y H:i') }}</div>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 text-center">
                <div class="text-xs text-slate-400">Timbang 2</div>
                <div class="text-xl font-bold font-mono">{{ number_format($ticket->second_weight, 0) }}</div>
                <div class="text-[10px] text-slate-400">{{ $ticket->second_weigh_at?->format('d/m/Y H:i') ?? '-' }}</div>
            </div>
            <div class="p-3 rounded-lg bg-amber-50 text-center col-span-2">
                <div class="text-xs text-slate-500">NETTO = GROSS - TARE</div>
                <div class="text-3xl font-black font-mono text-amber-600">{{ number_format($ticket->net, 0) }} kg</div>
                <div class="text-xs text-slate-400">Gross: {{ number_format($ticket->gross, 0) }} · Tare: {{ number_format($ticket->tare, 0) }} kg</div>
            </div>
        </div>
        @if ($ticket->weight_overridden)
            <div class="mt-3 p-3 rounded-lg bg-orange-50 border border-orange-200 text-xs text-orange-700">
                <strong>Berat dioverride.</strong> Alasan: {{ $ticket->override_reason }}
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @if ($ticket->status === 'FIRST_WEIGH')
        <form method="POST" action="{{ route('weighbridge.second', $ticket) }}" class="bg-white rounded-xl border border-slate-200 p-5">
            @csrf
            <h3 class="font-semibold text-slate-600 text-sm mb-3">Timbang Kedua</h3>
            <div class="grid md:grid-cols-2 gap-3">
                <div><label class="text-xs font-semibold text-slate-600 uppercase">Berat (kg)</label>
                <input type="number" step="0.01" name="weight" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 font-mono text-lg"></div>
                <div><label class="text-xs font-semibold text-slate-600 uppercase">Item/Material</label>
                <select name="item_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                    <option value="">--</option>
                    @foreach (\App\Models\Item::all() as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
                </select></div>
                <div><label class="text-xs font-semibold text-slate-600 uppercase">Customer</label>
                <select name="customer_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                    <option value="">--</option>
                    @foreach (\App\Models\Customer::where('status', true)->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select></div>
                <div><label class="text-xs font-semibold text-slate-600 uppercase">Supplier</label>
                <select name="supplier_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm">
                    <option value="">--</option>
                    @foreach (\App\Models\Supplier::where('status', true)->get() as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select></div>
            </div>
            <button class="mt-4 px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Simpan Timbang Kedua</button>
        </form>
        @endif

        @if ($ticket->status === 'COMPLETE')
        <form method="POST" action="{{ route('weighbridge.override', $ticket) }}" class="bg-white rounded-xl border border-slate-200 p-5">
            @csrf
            <h3 class="font-semibold text-slate-600 text-sm mb-3">Override Berat (tercatat di audit)</h3>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="text-xs text-slate-500 uppercase">Gross</label><input type="number" step="0.01" name="gross" value="{{ $ticket->gross }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
                <div><label class="text-xs text-slate-500 uppercase">Tare</label><input type="number" step="0.01" name="tare" value="{{ $ticket->tare }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
                <div class="col-span-3"><label class="text-xs text-slate-500 uppercase">Alasan (wajib)</label><input type="text" name="override_reason" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"></div>
            </div>
            <button class="mt-3 px-4 py-2 rounded-lg bg-orange-500 text-white text-sm">Override</button>
        </form>

        <form method="POST" action="{{ route('weighbridge.void', $ticket) }}" class="bg-white rounded-xl border border-red-200 p-5" onsubmit="return confirm('Void tiket ini?')">
            @csrf
            <h3 class="font-semibold text-red-600 text-sm mb-2">Void Tiket</h3>
            <input type="text" name="cancel_reason" placeholder="Alasan void (wajib)" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <button class="mt-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm">Void</button>
        </form>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 p-5 text-sm">
            <h3 class="font-semibold text-slate-600 mb-3">Info</h3>
            <dl class="space-y-1.5">
                <div class="flex justify-between"><dt class="text-slate-400">Nopol</dt><dd>{{ $ticket->vehicle_plate }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Pengemudi</dt><dd>{{ $ticket->driver_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Timbangan</dt><dd>{{ $ticket->weighbridge?->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Kalibrasi</dt><dd>{{ $ticket->calibration_version ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Operator</dt><dd>{{ $ticket->operator?->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Cetak Ulang</dt><dd>{{ $ticket->reprint_count }}x</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection