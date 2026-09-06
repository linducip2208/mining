@extends('layouts.app')
@section('title', ' - Pengguna')
@section('content')
<section class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6"><div><div class="section-kicker mb-2">Administration · access control</div><h1 class="text-[26px] font-bold tracking-[-.03em] text-slate-900">User management</h1><p class="mt-1 text-sm text-slate-500">Kelola identitas, scope site, dan akses operasional secara terkontrol.</p></div>
    <div class="shrink-0">
        @can('user.create')<x-ui.button size="sm" icon="plus" :href="route('users.create')">Tambah Pengguna</x-ui.button>@endcan
    </div>
</section>

<x-filter-bar :route="route('users.index')" class="command-filter print:hidden">
    <x-filter-input name="q" label="Cari" placeholder="Nama / username / email…" />
    <x-filter-input name="status" label="Status" type="select" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'SUSPENDED' => 'Suspended', 'LOCKED' => 'Terkunci']" />
    <x-filter-input name="role" label="Peran" type="select" :options="\App\Models\Role::orderBy('name')->pluck('name', 'code')->all()" />
</x-filter-bar>

<div class="dashboard-card overflow-hidden"><div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><div><h2 class="text-sm font-semibold text-slate-900">Directory</h2><p class="text-xs text-slate-400 mt-1">{{ $users->total() }} akun terdaftar</p></div><span class="text-xs text-slate-400">Last synced just now</span></div><x-ui.table>
    <x-slot:head>
        <th class="px-4 py-2.5">Nama</th><th class="px-4 py-2.5">Username</th><th class="px-4 py-2.5">Peran & Scope</th>
        <th class="px-4 py-2.5">Login Terakhir</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Aksi</th>
    </x-slot:head>
    @forelse ($users as $item)
    <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
        <td class="px-4 py-2.5">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 flex-none rounded-full bg-navy-900 dark:bg-white/10 text-amber-400 text-xs font-bold flex items-center justify-center">{{ strtoupper(substr($item->name, 0, 1)) }}</span>
                <span><span class="block font-medium">{{ $item->name }}</span><span class="block text-xs text-slate-400">{{ $item->email }}</span></span>
            </div>
        </td>
        <td class="px-4 py-2.5 font-mono text-xs">{{ $item->username }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $item->roles->pluck('name')->implode(', ') ?: '—' }}</td>
        <td class="px-4 py-2.5 text-xs text-slate-400">{{ $item->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
        <td class="px-4 py-2.5"><x-status-badge :status="$item->status" /></td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            <div class="relative inline-flex items-center" x-data="{ open: false }">
                @can('user.update')
                <a href="{{ route('users.edit', $item) }}" class="text-indigo-600 dark:text-indigo-400 text-xs font-medium hover:underline mr-2">Edit</a>
                @endcan
                <button @click="open=!open" aria-label="Aksi lainnya untuk {{ $item->name }}" class="p-1.5 rounded-md text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10"><x-ui.icon name="dots" class="w-[18px] h-[18px]" /></button>
                <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-4 mt-8 w-52 bg-white dark:bg-navy-800 rounded-card shadow-pop border border-slate-200 dark:border-slate-700 py-1.5 z-30 text-left text-[13px]">
                    <a href="{{ route('users.login-history', $item) }}" class="block px-4 py-2 hover:bg-slate-50 dark:hover:bg-white/5">Riwayat Login</a>
                    @can('user.update')
                    <button @click="$dispatch('open-modal', 'confirm-user-{{ $item->id }}-toggle'); open=false" class="w-full text-left px-4 py-2 hover:bg-slate-50 dark:hover:bg-white/5">{{ $item->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    <button @click="$dispatch('open-modal', 'confirm-user-{{ $item->id }}-reset'); open=false" class="w-full text-left px-4 py-2 hover:bg-slate-50 dark:hover:bg-white/5">Reset Password</button>
                    @if ($item->status === 'LOCKED')
                    <form action="{{ route('users.unlock', $item) }}" method="POST">@csrf<button class="w-full text-left px-4 py-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-500/10">Buka Kunci</button></form>
                    @endif
                    @endcan
                </div>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="6"><x-ui.empty-state icon="users" title="Belum ada pengguna" body="Tambah pengguna pertama untuk memberi akses ke sistem."><x-slot:action>@can('user.create')<x-ui.button size="sm" icon="plus" :href="route('users.create')">Tambah Pengguna</x-ui.button>@endcan</x-slot:action></x-ui.empty-state></td></tr>
    @endforelse
    <x-slot:footer>{{ $users->links('components.pagination') }}</x-slot:footer>
</x-ui.table></div>

{{-- Confirmation modals for sensitive actions --}}
@foreach ($users as $item)
@can('user.update')
<x-ui.modal name="confirm-user-{{ $item->id }}-toggle" title="Konfirmasi">
    <p class="text-sm">{{ $item->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }} akun <strong>{{ $item->name }}</strong> ({{ $item->username }})?</p>
    <form action="{{ route('users.toggle', $item) }}" method="POST" class="mt-4 flex justify-end gap-2">@csrf<x-ui.button>{{ $item->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}</x-ui.button></form>
</x-ui.modal>
<x-ui.modal name="confirm-user-{{ $item->id }}-reset" title="Reset Password">
    <p class="text-sm">Buat password sementara untuk <strong>{{ $item->name }}</strong>? Pengguna wajib menggantinya saat login berikutnya.</p>
    <form action="{{ route('users.reset-password', $item) }}" method="POST" class="mt-4 flex justify-end gap-2">@csrf<x-ui.button variant="danger">Reset Password</x-ui.button></form>
</x-ui.modal>
@endcan
@endforeach
@endsection
