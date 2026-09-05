@extends('layouts.app')
@section('title', ' - Jurnal')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Jurnal</h1>
    @can('journal.create')<x-btn-create label="Jurnal Manual" :href="route('journals.create')" />@endcan
</div>
<x-filter-bar :route="route('journals.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nomor jurnal..." />
    <x-filter-input name="status" label="Status" type="select" :options="array_combine($statuses, $statuses)" />
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>

@if ($journal)
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex justify-between">
        <div><span class="font-bold">{{ $journal->number }}</span> · {{ $journal->journal_date?->format('d/m/Y') }} · {{ $journal->memo }}</div>
        <x-status-badge :status="$journal->status" />
    </div>
    <table class="w-full text-sm mt-3">
        <thead><tr class="text-left text-[11px] uppercase text-slate-400 border-b"><th class="py-2">Akun</th><th class="py-2 text-right">Debit</th><th class="py-2 text-right">Kredit</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($journal->lines as $line)
            <tr>
                <td class="py-2">{{ $line->chartOfAccount?->code }} - {{ $line->chartOfAccount?->name }}</td>
                <td class="py-2 text-right">{{ $line->debit > 0 ? number_format($line->debit, 2, ',', '.') : '' }}</td>
                <td class="py-2 text-right">{{ $line->credit > 0 ? number_format($line->credit, 2, ',', '.') : '' }}</td>
            </tr>
            @endforeach
            <tr class="font-bold border-t-2 border-slate-300">
                <td class="py-2">TOTAL</td>
                <td class="py-2 text-right">{{ number_format($journal->total_debit, 2, ',', '.') }}</td>
                <td class="py-2 text-right">{{ number_format($journal->total_credit, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @if ($journal->status === 'POSTED')
    @can('journal.unpost')
    <form action="{{ route('journals.reverse', $journal) }}" method="POST" class="mt-3 flex gap-2 items-center" onsubmit="const r=prompt('Alasan reversal:'); if(r){this.reason.value=r}else{return false}">
        @csrf <input type="hidden" name="reason">
        <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-xs">Reverse Jurnal</button>
        <span class="text-xs text-slate-400">Reversal membuat jurnal cermin; jurnal asli tidak dapat diubah.</span>
    </form>
    @endcan
    @endif
</div>
@endif

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Sumber</th>
        <th class="px-4 py-2.5 text-right">Debit</th><th class="px-4 py-2.5 text-right">Kredit</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->journal_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5 text-xs">
                @php $link = $item->sourceLink(); @endphp
                @if ($link)
                    <a href="{{ route($link[0], $link[1]) }}" class="text-amber-600 hover:underline">{{ $item->source_number ?? $item->source_type }}</a>
                @else
                    {{ $item->source_number ?? 'Manual' }}
                @endif
            </td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->total_debit, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->total_credit, 0, ',', '.') }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5"><a href="{{ route('journals.show', $item) }}" class="text-amber-600 text-xs hover:underline">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada jurnal</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection