@extends('layouts.app')
@section('title', ' - Audit Trail')
@section('content')
<h1 class="text-xl font-bold text-slate-800 mb-4">Audit Trail</h1>
<x-filter-bar :route="route('audit.index')">
    <x-filter-input name="q" label="Modul" placeholder="Modul / record..." />
    <x-filter-input name="action" label="Aksi" type="select" :options="['CREATE' => 'CREATE', 'UPDATE' => 'UPDATE', 'DELETE' => 'DELETE', 'SUBMIT' => 'SUBMIT', 'APPROVE' => 'APPROVE', 'REJECT' => 'REJECT', 'POST' => 'POST', 'PRINT' => 'PRINT', 'EXPORT' => 'EXPORT', 'VOID' => 'VOID', 'LOGIN' => 'LOGIN', 'LOGOUT' => 'LOGOUT']" />
    <x-filter-input name="from" label="Dari" type="date" /><x-filter-input name="to" label="Sampai" type="date" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Waktu</th><th class="px-4 py-2.5">Pengguna</th><th class="px-4 py-2.5">Aksi</th>
        <th class="px-4 py-2.5">Modul</th><th class="px-4 py-2.5">Record</th><th class="px-4 py-2.5">IP</th><th class="px-4 py-2.5">Alasan</th>
    </x-slot:head>
    <tbody>
        @forelse ($logs as $log)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 text-xs">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
            <td class="px-4 py-2.5">{{ $log->user?->name ?? 'Sistem' }}</td>
            <td class="px-4 py-2.5"><span class="text-xs font-bold {{ in_array($log->action, ['DELETE','REJECT','VOID']) ? 'text-red-600' : (in_array($log->action, ['CREATE','APPROVE','POST']) ? 'text-green-600' : 'text-slate-600') }}">{{ $log->action }}</span></td>
            <td class="px-4 py-2.5 text-xs">{{ $log->module }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $log->record_type }}#{{ $log->record_id }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $log->ip_address }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $log->reason }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada aktivitas</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $logs->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection