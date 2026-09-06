<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Mining ERP') }}@yield('title')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛏️</text></svg>">
    <script>
        // restore theme + sidebar state pre-paint (no FOUC)
        try {
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
            if (localStorage.getItem('sb-collapsed') === '1') document.documentElement.classList.add('sb-collapsed');
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-800 dark:bg-navy-950 dark:text-slate-200">
<a href="#mainContent" class="sr-only focus:not-sr-only focus:absolute focus:z-[60] focus:px-4 focus:py-2 focus:bg-amber-500 focus:text-white focus:rounded-md focus:m-2">Lewati ke konten</a>
<div class="min-h-screen flex">
    @include('layouts.partials.sidebar')

    <div id="appMain" class="flex-1 flex flex-col min-w-0 lg:ml-64">
        <header class="h-16 bg-white dark:bg-navy-900 border-b border-slate-200 dark:border-slate-700/60 sticky top-0 z-20 flex items-center gap-2 px-4 lg:px-6">
            <button class="p-2 rounded-ctl hover:bg-slate-100 dark:hover:bg-white/5 text-slate-500" onclick="window.toggleSidebar()" aria-label="Buka atau tutup sidebar">
                <x-ui.icon name="menu" class="w-5 h-5" />
            </button>

            {{-- Breadcrumb / location --}}
            @php
                $rn = request()->route()?->getName() ?? '';
                $crumbs = collect(explode('.', $rn))->filter()->map(fn ($s) => ucwords(str_replace(['-', '_'], ' ', $s)))->take(3);
            @endphp
            <nav aria-label="Lokasi halaman" class="hidden md:block text-sm text-slate-400 dark:text-slate-500 truncate">
                <a href="{{ route('dashboard') }}" class="hover:text-amber-600">Dashboard</a>
                @foreach ($crumbs as $c)
                    @if (!$loop->first || $c !== 'Dashboard')
                    <span class="mx-1 text-slate-300 dark:text-slate-600">/</span><span class="{{ $loop->last ? 'text-slate-700 dark:text-slate-200 font-medium' : '' }}">{{ $c }}</span>
                    @endif
                @endforeach
            </nav>

            {{-- Command palette trigger --}}
            <button type="button" onclick="document.getElementById('cmdk').classList.remove('hidden');document.getElementById('cmdkInput').focus()"
                class="hidden sm:flex flex-1 max-w-md mx-auto items-center gap-2 px-3 py-2 text-sm rounded-ctl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-white/5 text-slate-400 hover:border-slate-300">
                <x-ui.icon name="search" class="w-4 h-4" />
                <span class="flex-1 text-left">Cari invoice, DO, PO, tiket…</span>
                <kbd class="text-[10px] px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-navy-800 font-sans">Ctrl K</kbd>
            </button>

            <div class="flex items-center gap-1 ml-auto">
                {{-- Dark toggle --}}
                <button type="button" onclick="toggleTheme()" title="Mode gelap / terang" aria-label="Alihkan mode gelap"
                    class="p-2 rounded-ctl hover:bg-slate-100 dark:hover:bg-white/5 text-slate-500 dark:text-slate-400">
                    <span class="hidden dark:inline"><x-ui.icon name="sun" class="w-5 h-5" /></span>
                    <span class="dark:hidden"><x-ui.icon name="moon" class="w-5 h-5" /></span>
                </button>
                {{-- Contextual help --}}
                @php $docsUrl = \App\Docs\DocRegistry::urlForRoute($rn); @endphp
                <a href="{{ $docsUrl }}" target="_blank" title="Bantuan halaman ini" aria-label="Buka bantuan halaman ini"
                   class="p-2 rounded-ctl hover:bg-slate-100 dark:hover:bg-white/5 text-slate-500 font-bold text-lg leading-none">?</a>
                {{-- Pending approvals --}}
                @php
                    $pendingCount = auth()->user()->isSuperAdmin()
                        ? \App\Models\ApprovalRequest::where('status','PENDING')->count()
                        : \App\Services\ApprovalService::pendingFor(auth()->user())->count();
                @endphp
                <a href="{{ route('approval.index') }}" title="Persetujuan ({{ $pendingCount }} tertunda)" aria-label="Pusat persetujuan"
                   class="relative p-2 rounded-ctl hover:bg-slate-100 dark:hover:bg-white/5 text-slate-500 dark:text-slate-400">
                    <x-ui.icon name="shield" class="w-5 h-5" />
                    @if ($pendingCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 text-[10px] font-bold bg-red-500 text-white rounded-full flex items-center justify-center">{{ $pendingCount }}</span>
                    @endif
                </a>

                {{-- Notifications --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" aria-label="Notifikasi" class="relative p-2 rounded-ctl hover:bg-slate-100 dark:hover:bg-white/5 text-slate-500 dark:text-slate-400">
                        <x-ui.icon name="bell" class="w-5 h-5" />
                        @php $unreadNotif = auth()->user()->unreadNotifications()->count(); @endphp
                        @if ($unreadNotif > 0)
                            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 text-[10px] font-bold bg-amber-500 text-white rounded-full flex items-center justify-center">{{ $unreadNotif }}</span>
                        @endif
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-2 w-80 bg-white dark:bg-navy-800 rounded-card shadow-pop border border-slate-200 dark:border-slate-700 py-2 max-h-96 overflow-y-auto nice-scroll z-50">
                        <div class="px-4 py-2 text-xs font-semibold text-slate-400 uppercase">Notifikasi</div>
                        @forelse (auth()->user()->unreadNotifications()->latest()->limit(10)->get() as $notif)
                            @php
                                $etype = $notif->data['type'] ?? 'INFO';
                                $prio = in_array($etype, ['FUEL_ANOMALY', 'FUEL_DIP_VARIANCE', 'EQUIPMENT_BREAKDOWN', 'SAFETY_INCIDENT']) ? 'critical'
                                    : (in_array($etype, ['BUDGET_EXCEEDED', 'OVERDUE_AP', 'COMPLIANCE_EXPIRY', 'HSE_PERMIT_EXPIRY', 'QUALITY_FAILURE', 'STOCK_VARIANCE', 'HIGH_DOWNTIME', 'LOW_PRODUCTION']) ? 'warning' : 'info');
                            @endphp
                            <div class="px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-white/5 border-b border-slate-100 dark:border-slate-700/50 {{ $prio === 'critical' ? 'border-l-2 border-l-red-500' : '' }}">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded {{ $prio === 'critical' ? 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-300' : ($prio === 'warning' ? 'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300' : 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-300') }}">{{ $etype }}</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $notif->data['title'] ?? 'Notifikasi' }}</span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $notif->data['body'] ?? '' }}</div>
                            </div>
                        @empty
                            <div class="px-4 py-6 text-sm text-slate-400 text-center">Tidak ada notifikasi</div>
                        @endforelse
                    </div>
                </div>

                {{-- User menu --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" aria-label="Menu pengguna" class="flex items-center gap-2 p-1.5 pr-2.5 rounded-ctl hover:bg-slate-100 dark:hover:bg-white/5">
                        <div class="w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex items-center justify-center">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div class="hidden md:block text-left">
                            <div class="text-sm font-semibold leading-tight text-slate-700 dark:text-slate-200">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] text-slate-400 leading-tight">{{ auth()->user()->roles->first()?->name ?? '—' }}</div>
                        </div>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-2 w-52 bg-white dark:bg-navy-800 rounded-card shadow-pop border border-slate-200 dark:border-slate-700 py-1.5 z-50 text-sm">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 dark:hover:bg-white/5 text-slate-700 dark:text-slate-200"><x-ui.icon name="user" class="w-4 h-4 text-slate-400" />Profil Saya</a>
                        <a href="{{ route('password.change') }}" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 dark:hover:bg-white/5 text-slate-700 dark:text-slate-200"><x-ui.icon name="shield" class="w-4 h-4 text-slate-400" />Keamanan</a>
                        <a href="/docs" target="_blank" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 dark:hover:bg-white/5 text-slate-700 dark:text-slate-200"><x-ui.icon name="book-open" class="w-4 h-4 text-slate-400" />Dokumentasi</a>
                        <div class="my-1.5 border-t border-slate-100 dark:border-slate-700/60"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10"><x-ui.icon name="arrow-right" class="w-4 h-4" />Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Command palette (Ctrl+K) --}}
        <div id="cmdk" class="hidden fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Pencarian cepat">
            <div class="absolute inset-0 bg-slate-900/50" onclick="document.getElementById('cmdk').classList.add('hidden')"></div>
            <div class="relative mx-auto mt-24 w-[min(36rem,92vw)] bg-white dark:bg-navy-800 rounded-card shadow-pop border border-slate-200 dark:border-slate-700 overflow-hidden animate-fadeup">
                <form action="{{ route('search') }}" method="GET" class="flex items-center gap-2 px-4 border-b border-slate-100 dark:border-slate-700/60">
                    <x-ui.icon name="search" class="w-4 h-4 text-slate-400" />
                    <input id="cmdkInput" type="search" name="q" autocomplete="off" placeholder="Cari invoice, DO, PO, tiket, customer…"
                        class="flex-1 py-3.5 text-sm bg-transparent outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400">
                    <kbd class="text-[10px] px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 text-slate-400">ESC</kbd>
                </form>
                <div class="p-2 text-[13px]">
                    <div class="px-3 py-1.5 text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Aksi cepat</div>
                    @foreach ([['Dashboard', 'dashboard', 'dashboard'], ['Persetujuan Saya', 'approval.index', 'check-circle'], ['Timbangan', 'weighbridge-tickets.index', 'scale'], ['Dispatch Board', 'dispatch.dashboard', 'truck'], ['Dokumentasi', null, 'book-open']] as [$label, $r, $ic])
                    <a @if($r) href="{{ route($r) }}" @else href="/docs" target="_blank" @endif class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 text-slate-700 dark:text-slate-200">
                        <x-ui.icon :name="$ic" class="w-4 h-4 text-slate-400" />{{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        <script>
            function toggleTheme() {
                var dark = document.documentElement.classList.toggle('dark');
                try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
            }
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    document.getElementById('cmdk').classList.remove('hidden');
                    document.getElementById('cmdkInput').focus();
                }
                if (e.key === 'Escape') document.getElementById('cmdk').classList.add('hidden');
            });
        </script>

        <main id="mainContent" class="flex-1 p-4 lg:p-6 min-w-0" tabindex="-1">
            @if (session('success'))
                <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert type="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
