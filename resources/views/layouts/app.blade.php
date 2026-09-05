<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Mining ERP') }}@yield('title')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛏️</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-800">
<div class="min-h-screen flex">
    @include('layouts.partials.sidebar')

    <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 flex items-center gap-4 px-4 lg:px-6">
            <button id="sidebarToggle" class="lg:hidden p-2 rounded-md hover:bg-slate-100" onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            {{-- Global search --}}
            <form action="{{ route('search') }}" method="GET" class="flex-1 max-w-xl">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari invoice, DO, PO, timbang ticket, customer..."
                           class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
            </form>

            <div class="flex items-center gap-1 ml-auto">
                {{-- Pending approvals --}}
                @php
                    $pendingCount = auth()->user()->isSuperAdmin()
                        ? \App\Models\ApprovalRequest::where('status','PENDING')->count()
                        : \App\Services\ApprovalService::pendingFor(auth()->user())->count();
                @endphp
                <a href="{{ route('approval.index') }}" title="Persetujuan" class="relative p-2 rounded-md hover:bg-slate-100 text-slate-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.5 11 14.5 15 10.5M12 3l7 4v5c0 4.5-3 8-7 9-4-1-7-4.5-7-9V7l7-4z"/></svg>
                    @if ($pendingCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 text-[10px] font-bold bg-red-500 text-white rounded-full flex items-center justify-center">{{ $pendingCount }}</span>
                    @endif
                </a>

                {{-- Notifications --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" class="relative p-2 rounded-md hover:bg-slate-100 text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 1 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/></svg>
                        @php $unreadNotif = auth()->user()->unreadNotifications()->count(); @endphp
                        @if ($unreadNotif > 0)
                            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 text-[10px] font-bold bg-amber-500 text-white rounded-full flex items-center justify-center">{{ $unreadNotif }}</span>
                        @endif
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 py-2 max-h-96 overflow-y-auto z-50">
                        <div class="px-4 py-2 text-xs font-semibold text-slate-400 uppercase">Notifikasi</div>
                        @forelse (auth()->user()->unreadNotifications()->latest()->limit(10)->get() as $notif)
                            <div class="px-4 py-2.5 hover:bg-slate-50 border-b border-slate-100">
                                <div class="text-sm font-medium text-slate-700">{{ $notif->data['title'] ?? 'Notifikasi' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $notif->data['body'] ?? '' }}</div>
                            </div>
                        @empty
                            <div class="px-4 py-6 text-sm text-slate-400 text-center">Tidak ada notifikasi</div>
                        @endforelse
                    </div>
                </div>

                {{-- Profile --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" class="flex items-center gap-2 p-1.5 pr-3 rounded-lg hover:bg-slate-100">
                        <div class="w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex items-center justify-center">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div class="hidden md:block text-left">
                            <div class="text-sm font-semibold leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] text-slate-400 leading-tight">{{ auth()->user()->roles->first()?->name ?? '—' }}</div>
                        </div>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                        <a href="{{ \Illuminate\Support\Facades\Route::has('profile.edit') ? route('profile.edit') : url('/profile/security') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">Profil Saya</a>
                        <a href="{{ route('password.change') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">Ganti Password</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-6">
            @if (session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
