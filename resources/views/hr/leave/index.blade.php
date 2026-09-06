@extends('layouts.app')
@php use App\Support\HumanLabel; @endphp
@section('title', ' - Cuti')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Pengajuan Cuti</h1>
    @can('leave.create')<x-btn-create :href="route('leaves.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nomor</th><th class="px-4 py-2.5">Karyawan</th><th class="px-4 py-2.5">Tipe</th>
        <th class="px-4 py-2.5">Periode</th><th class="px-4 py-2.5 text-right">Hari</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($items as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5">{{ $item->number }}</td>
            <td class="px-4 py-2.5">{{ $item->employee?->name }}</td>
            <td class="px-4 py-2.5 text-xs">{{ HumanLabel::label($item->type) }}</td>
            <td class="px-4 py-2.5">{{ $item->start_date?->format('d/m') }} - {{ $item->end_date?->format('d/m/Y') }}</td>
            <td class="px-4 py-2.5 text-right">{{ $item->days }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right">
                @if ($item->status === 'SUBMITTED')
                <form action="{{ route('leaves.approve', $item) }}" method="POST" class="inline">@csrf
                <button class="text-xs text-green-600 hover:underline">Setujui</button></form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada pengajuan</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $items->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection
