@extends('layouts.app')
@section('title', ' - Lembur')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Lembur</h1>
    @can('overtime.create')<x-btn-create :href="route('overtimes.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Tanggal</th><th class="px-4 py-2.5">Karyawan</th>
        <th class="px-4 py-2.5 text-right">Jam</th><th class="px-4 py-2.5">Alasan</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5">{{ $item->employee?->name }}</td>
            <td class="px-4 py-2.5 text-right">{{ number_format($item->hours, 1) }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->reason }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if (in_array($item->status, ['DRAFT', 'SUBMITTED']))
                <form action="{{ route('overtimes.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                <form action="{{ route('overtimes.reject', $item) }}" method="POST" class="inline ml-2">@csrf
                <button class="text-xs text-red-600 hover:underline">Tolak</button></form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada lembur</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection