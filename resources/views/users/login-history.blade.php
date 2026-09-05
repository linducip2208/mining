@extends('layouts.app')

@section('title', ' - Riwayat Login')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Riwayat Login</h1>
        <p class="text-sm text-slate-500">{{ $user->name }} ({{ $user->username }})</p>
    </div>
    <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm">Kembali</a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <x-stat-card title="Total Login" :value="$history->where('event', 'LOGIN')->count()" color="green" />
    <x-stat-card title="Gagal Login" :value="$history->where('event', 'FAILED')->count()" color="red" />
    <x-stat-card title="Terkunci" :value="$history->where('event', 'LOCKOUT')->count()" color="amber" />
    <x-stat-card title="Login Terakhir" :value="$user->last_login_at?->format('d/m/Y H:i') ?? '-'" color="slate" :sub="$user->last_login_ip ?? ''" />
</div>

<x-table>
    <x-slot:head>
        <th class="px-4 py-2.5">Waktu</th><th class="px-4 py-2.5">Event</th><th class="px-4 py-2.5">IP Address</th><th class="px-4 py-2.5">Perangkat</th>
    </x-slot:head>
    <tbody>
        @forelse ($history as $h)
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-2.5 text-xs">{{ $h->created_at?->format('d/m/Y H:i:s') }}</td>
            <td class="px-4 py-2.5">
                <span class="text-xs font-bold {{ $h->event === 'FAILED' || $h->event === 'LOCKOUT' ? 'text-red-600' : ($h->event === 'LOGIN' ? 'text-green-600' : 'text-slate-500') }}">{{ $h->event }}</span>
            </td>
            <td class="px-4 py-2.5 text-xs">{{ $h->ip_address }}</td>
            <td class="px-4 py-2.5 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($h->user_agent, 60) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada riwayat</td></tr>
        @endforelse
    </tbody>
</x-table>
@endsection
