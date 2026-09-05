@extends('layouts.app')
@section('title', ' - Pengguna')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Manajemen Pengguna</h1>
    @can('user.create')<x-btn-create :href="route('users.create')" />@endcan
</div>
<x-filter-bar :route="route('users.index')">
    <x-filter-input name="q" label="Cari" placeholder="Nama / username..." />
    <x-filter-input name="status" label="Status" type="select" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'SUSPENDED' => 'Suspended', 'LOCKED' => 'Terkunci']" />
</x-filter-bar>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Username</th><th class="px-4 py-2.5">Email</th>
        <th class="px-4 py-2.5">Peran</th><th class="px-4 py-2.5">Login Terakhir</th><th class="px-4 py-2.5">Status</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($users as $item)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $item->name }}</td>
            <td class="px-4 py-2.5">{{ $item->username }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->email }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->roles->pluck('name')->implode(', ') }}</td>
            <td class="px-4 py-2.5 text-xs">{{ $item->last_login_at?->format('d/m/Y H:i') ?? '-' }}</td>
            <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                @can('user.update')
                <a href="{{ route('users.edit', $item) }}" class="text-indigo-600 text-xs hover:underline">Edit</a>
                <form action="{{ route('users.toggle', $item) }}" method="POST" class="inline ml-1">@csrf
                    <button class="text-xs text-slate-500 hover:underline">{{ $item->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                <form action="{{ route('users.reset-password', $item) }}" method="POST" class="inline ml-1" onsubmit="return confirm('Reset password user ini?')">@csrf
                    <button class="text-xs text-amber-600 hover:underline">Reset Pass</button></form>
                @endcan
                @if ($item->status === 'LOCKED')
                <form action="{{ route('users.unlock', $item) }}" method="POST" class="inline ml-1">@csrf
                    <button class="text-xs text-green-600 hover:underline">Buka Kunci</button></form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada pengguna</td></tr>
        @endforelse
    </tbody>
    <x-slot:footer>{{ $users->links('components.pagination') }}</x-slot:footer>
</x-table>
@endsection