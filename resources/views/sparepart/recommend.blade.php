@extends('layouts.app')
@section('title', ' - Rekomendasi Pembelian')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-800">Rekomendasi Pembelian</h1>
    <form method="GET" class="flex gap-2">
        <select name="warehouse_id" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]"><option value="">Semua gudang</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected($warehouseId == $w->id)>{{ $w->name }}</option>@endforeach</select>
    </form>
</div>
<p class="text-sm text-slate-500 mb-4">recommended = max_stock − available. Membuat PR — bukan pembelian otomatis.</p>

<form method="POST" action="{{ route('sparepart.recommend.pr') }}">
    @csrf
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div><label class="text-xs font-semibold text-slate-600 uppercase">Perusahaan (untuk PR)</label>
            <select name="company_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm min-h-[44px]">@foreach(\App\Models\Company::orderBy('name')->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
    </div>
    <x-table>
        <x-slot:head>
            <th class="px-4 py-2.5">Sparepart</th><th class="px-4 py-2.5 text-right">Available</th><th class="px-4 py-2.5 text-right">Max</th>
            <th class="px-4 py-2.5 text-right">Rekomendasi</th><th class="px-4 py-2.5 text-right">Qty PR</th>
        </x-slot:head>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-2.5 font-mono text-xs">{{ $r['item']->code }} - {{ $r['item']->name }} ({{ $r['status'] }})</td>
                <td class="px-4 py-2.5 text-right">{{ $r['avail'] }}</td>
                <td class="px-4 py-2.5 text-right">{{ $r['item']->max_stock ?? '-' }}</td>
                <td class="px-4 py-2.5 text-right font-semibold">{{ $r['recommended'] }}</td>
                <td class="px-4 py-2.5 text-right"><input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $r['item']->id }}"><input name="items[{{ $i }}][qty]" type="number" step="0.0001" min="0" value="{{ $r['recommended'] }}" class="w-28 px-2 py-1.5 rounded border border-slate-200 text-sm text-right"></td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada rekomendasi. Stok aman.</td></tr>
            @endforelse
        </tbody>
    </x-table>
    @if(count($rows) > 0)
    <div class="mt-4"><button class="px-5 py-2.5 rounded-lg bg-amber-500 text-white text-sm font-semibold min-h-[44px]">Buat PR dari Rekomendasi</button></div>
    @endif
</form>
@endsection
