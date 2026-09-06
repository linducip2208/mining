@extends('layouts.app')

@section('title', ' - Quality Hold')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold text-slate-800">Quality Hold</h1>
    <p class="text-sm text-slate-500">Delivery yang di-hold tidak bisa complete sampai release / special approve</p>
</div>

@can('quality.create')
<form method="POST" action="{{ route('quality-holds.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    @csrf
    <div class="grid md:grid-cols-5 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">ID Sales Order</label>
            <input type="number" name="sales_order_id" placeholder="opsional" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">ID Surat Jalan</label>
            <input type="number" name="delivery_order_id" placeholder="opsional" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Material</label>
            <select name="item_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach (\App\Models\Item::whereIn('type', ['PRODUCT', 'RAW'])->orderBy('name')->get() as $it)
                    <option value="{{ $it->id }}">{{ $it->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Customer</label>
            <select name="customer_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm outline-none">
                <option value="">-- Pilih --</option>
                @foreach (\App\Models\Customer::where('status', true)->orderBy('name')->get() as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-600 uppercase">Alasan</label>
            <input type="text" name="reason" required maxlength="2000" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none">
        </div>
    </div>
    <button type="submit" class="mt-3 px-5 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">Buat Hold</button>
</form>
@endcan

<x-filter-bar :route="route('quality-holds.index')">
    <x-filter-input name="status" label="Status" type="select" :options="['HOLD' => 'Hold', 'RELEASED' => 'Released', 'CANCELLED' => 'Batal']" />
</x-filter-bar>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Tanggal</th>
        <th class="px-4 py-2.5">SO / DO</th>
        <th class="px-4 py-2.5">Material</th>
        <th class="px-4 py-2.5">Alasan</th>
        <th class="px-4 py-2.5">Status</th>
        <th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($items as $item)
    <tr class="hover:bg-slate-50">
        <td class="px-4 py-2.5">{{ $item->created_at?->format('Y-m-d') }}</td>
        <td class="px-4 py-2.5 text-xs">SO #{{ $item->sales_order_id ?? '—' }} / DO #{{ $item->delivery_order_id ?? '—' }}</td>
        <td class="px-4 py-2.5">{{ $item->item?->name ?? '—' }}</td>
        <td class="px-4 py-2.5 text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($item->reason, 80) }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            @if ($item->status === 'HOLD')
                @can('quality.release')
                <form method="POST" action="{{ route('quality-holds.release', $item) }}" class="inline">
                    @csrf<button class="text-green-600 hover:underline text-xs">Release</button>
                </form>
                @endcan
                @can('quality.create')
                <form method="POST" action="{{ route('quality-holds.special-approve', $item) }}" class="inline ml-2" onsubmit="return confirm('Ajukan special approval ke approval center?')">
                    @csrf
                    <button class="text-amber-600 hover:underline text-xs">Ajukan Special</button>
                </form>
                @endcan
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data</td></tr>
    @endforelse
    <x-slot:footer>
        {{ $items->links('components.pagination') }}
    </x-slot:footer>
</x-table>
@endsection
