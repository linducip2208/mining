@extends('layouts.app')
@section('title', ' - Peran')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-800">Manajemen Peran</h1>
    @can('role.create')<x-btn-create :href="route('role.create')" />@endcan
</div>
<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Kode</th><th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5 text-center">Jumlah Izin</th>
        <th class="px-4 py-2.5 text-center">Jumlah Pengguna</th><th class="px-4 py-2.5">Sistem</th><th></th>
    </x-slot:head>
    <tbody>
        @forelse ($roles as $role)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 font-medium">{{ $role->code }}</td>
            <td class="px-4 py-2.5">{{ $role->name }}</td>
            <td class="px-4 py-2.5 text-center">{{ $role->permissions_count }}</td>
            <td class="px-4 py-2.5 text-center">{{ $role->users_count }}</td>
            <td class="px-4 py-2.5">{{ $role->is_system ? 'Ya' : '-' }}</td>
            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                <a href="{{ route('role.show', $role) }}" class="text-amber-600 text-xs hover:underline">Matriks Izin</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada peran</td></tr>
        @endforelse
    </tbody>
</x-table>
@endsection