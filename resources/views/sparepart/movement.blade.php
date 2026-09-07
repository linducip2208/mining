@extends('layouts.app')
@section('title', $mode === 'in' ? ' - Barang Masuk' : ' - Barang Keluar')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">{{ $mode === 'in' ? 'Barang Masuk' : 'Barang Keluar' }}</h1>
    <form method="GET" action="{{ route('sparepart.scan') }}" class="flex gap-2">
        <input name="code" placeholder="Scan barcode/QR..." autofocus class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-mono min-h-[44px] w-56" aria-label="Scan sparepart">
        <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm min-h-[44px]">Cari</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <form method="POST" action="{{ $mode === 'in' ? route('sparepart.receipt.store') : route('sparepart.issue.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @csrf
        <select name="item_id" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] md:col-span-2"><option value="">Sparepart --</option>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->code }} - {{ $i->name }}</option>@endforeach</select>
        <select name="warehouse_id" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Gudang --</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
        <input name="qty" type="number" step="0.0001" min="0.0001" required placeholder="Qty" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        @if($mode === 'in')
        <input type="date" name="receipt_date" value="{{ today()->toDateString() }}" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <input name="unit_cost" type="number" step="0.01" min="0" placeholder="Cost (opsional)" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <select name="condition" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">@foreach(['BAIK' => 'Baik', 'RUSAK' => 'Rusak', 'REPAIR' => 'Repair', 'REJECTED' => 'Rejected (tidak masuk stok)'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
        <select name="supplier_id" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Supplier --</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
        <input name="reference_no" maxlength="100" placeholder="No referensi" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" name="is_opening" value="1" class="w-5 h-5"> Stok lama (OPENING BALANCE, tanggal efektif jelas)</label>
        @else
        <input type="date" name="issue_date" value="{{ today()->toDateString() }}" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        <select name="reason" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">@foreach(['MAINTENANCE' => 'Maintenance', 'WORK_ORDER' => 'Work Order', 'TRANSFER' => 'Transfer', 'CONSUMPTION' => 'Konsumsi', 'RETURN_TO_VENDOR' => 'Retur Vendor', 'ADJUSTMENT' => 'Adjustment', 'OTHER' => 'Lainnya'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
        <select name="work_order_id" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">WO (opsional → biaya+ jurnal) --</option>@foreach($workOrders as $w)<option value="{{ $w->id }}">{{ $w->number }}</option>@endforeach</select>
        <select name="equipment_id" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Equipment --</option>@foreach($equipment as $e)<option value="{{ $e->id }}">{{ $e->code }} - {{ $e->name }}</option>@endforeach</select>
        <input name="received_by" maxlength="150" placeholder="Diterima oleh" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        @endif
        <input name="notes" placeholder="Keterangan" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px] md:col-span-2">
        <div class="md:col-span-4"><button class="px-5 py-2.5 rounded-lg {{ $mode === 'in' ? 'bg-emerald-600' : 'bg-amber-500' }} text-white text-sm font-semibold min-h-[44px]">{{ $mode === 'in' ? 'Catat Masuk (ledger)' : 'Catat Keluar (ledger)' }}</button></div>
    </form>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Referensi</th><th class="px-4 py-2.5">Sparepart</th>
        <th class="px-4 py-2.5 text-right">{{ $mode === 'in' ? 'Masuk' : 'Keluar' }}</th><th class="px-4 py-2.5">Gudang</th>
    </x-slot:head>
    <tbody>
        @forelse ($movements as $m)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 text-xs whitespace-nowrap">{{ $m->trx_date }}</td>
            <td class="px-4 py-2.5 text-xs font-mono">{{ $m->ref_number }}</td>
            <td class="px-4 py-2.5 font-mono text-xs">{{ $m->item?->code }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($mode === 'in' ? $m->qty_in : $m->qty_out, 2) }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $m->warehouse?->code }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada movement</td></tr>
        @endforelse
    </tbody>
</x-table>
@endsection
