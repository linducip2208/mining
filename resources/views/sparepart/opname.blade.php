@extends('layouts.app')
@section('title', ' - Stock Opname')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Stock Opname Sparepart</h1>
    <a href="{{ route('stock-adjustments.index') }}" class="text-sm text-amber-600 hover:underline">Semua Adjustment →</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="font-semibold text-sm mb-3">Opname Baru (DRAFT → COUNTING)</h2>
    <form method="POST" action="{{ route('sparepart.opname.store') }}" id="opnameForm">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <select name="warehouse_id" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Gudang --</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
            <input type="date" name="adjustment_date" value="{{ today()->toDateString() }}" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
            <input name="reason" placeholder="Alasan opname" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
        </div>
        <div id="opnameLines" class="space-y-2">
            <div class="grid grid-cols-[1fr_8rem] gap-2">
                <select name="lines[0][item_id]" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Sparepart --</option>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->code }} - {{ $i->name }}</option>@endforeach</select>
                <input name="lines[0][counted_qty]" type="number" step="0.0001" min="0" required placeholder="Fisik" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-3">
            <button type="button" onclick="addOpnameLine()" class="px-4 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">+ Baris</button>
            <button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Buat Opname</button>
        </div>
    </form>
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5"></th>
    </x-slot:head>
    <tbody>
        @forelse ($adjustments as $a)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-mono whitespace-nowrap">{{ $a->number }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $a->adjustment_date }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$a->status" /></td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                @if($a->status === 'COUNTING')
                <form action="{{ route('sparepart.opname.review', $a) }}" method="POST" class="inline">@csrf<button class="text-xs text-indigo-600 hover:underline">Selesai Counting → Review</button></form>
                @endif
                <a href="{{ route('stock-adjustments.show', $a) }}" class="text-amber-600 hover:underline text-xs ml-2">Proses</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada opname</td></tr>
        @endforelse
    </tbody>
</x-table>
<script>
let opIdx = 1;
function addOpnameLine() {
    const wrap = document.getElementById('opnameLines');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-[1fr_8rem] gap-2';
    div.innerHTML = `<select name="lines[${opIdx}][item_id]" required class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Sparepart --</option>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->code }} - {{ $i->name }}</option>@endforeach</select><input name="lines[${opIdx}][counted_qty]" type="number" step="0.0001" min="0" required placeholder="Fisik" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">`;
    wrap.appendChild(div);
    opIdx++;
}
</script>
@endsection
