<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] }}</title>
    <meta name="description" content="{{ $meta['description'] }}">
    <link rel="canonical" href="{{ $meta['canonical'] }}">
    <meta name="robots" content="{{ $meta['robots'] }}">
    <meta name="theme-color" content="#0f172a">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $meta['description'] }}">
    <meta property="og:url" content="{{ $meta['canonical'] }}">
    <meta property="og:image" content="{{ asset('icons/icon-512.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $meta['title'] }}">
    <meta name="twitter:description" content="{{ $meta['description'] }}">
    <meta name="twitter:image" content="{{ asset('icons/icon-512.png') }}">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @vite(['resources/css/app.css'])
    @if(\App\Models\Setting::get('marketing.analytics_enabled', false) && \App\Models\Setting::get('marketing.analytics_id'))
    <!-- analytics: {{\App\Models\Setting::get('marketing.analytics_provider', 'custom')}} {{\App\Models\Setting::get('marketing.analytics_id')}} -->
    @endif
</head>
<body class="bg-slate-50 text-slate-800 antialiased pb-20 sm:pb-0">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:z-[60] focus:px-4 focus:py-2 focus:bg-amber-500 focus:text-white focus:rounded-md focus:m-2">Lewati ke konten</a>
    <header class="sticky top-0 z-40 bg-[#0f172a]/95 backdrop-blur border-b border-white/10">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
            <a href="/" class="flex items-center gap-2.5 min-w-0" aria-label="Mining ERP">
                <img src="{{ asset('icons/icon-192.png') }}" alt="Mining ERP" width="36" height="36" class="w-9 h-9 rounded-lg">
                <span class="font-bold text-white tracking-wide truncate">Mining ERP</span>
            </a>
            <nav class="hidden md:flex items-center gap-6 text-sm text-slate-300" aria-label="Navigasi utama">
                <a href="/fitur-erp-mining" class="hover:text-white">Fitur</a>
                <a href="/harga-erp-tambang" class="hover:text-white">Harga</a>
                <a href="/source-code-erp-mining" class="hover:text-white">Source Code</a>
                <a href="/cari-solusi" class="hover:text-white">Cari Solusi</a>
            </nav>
            <x-marketing.whatsapp-cta label="Tanya via WhatsApp" position="header" type="header" compact="1" />
        </div>
    </header>
    <main id="konten">
        @yield('content')
    </main>
    <footer class="mt-16 bg-[#0f172a] text-slate-400">
        <div class="max-w-6xl mx-auto px-4 py-10 grid gap-8 md:grid-cols-3 text-sm">
            <div>
                <div class="font-bold text-white mb-2">Mining ERP</div>
                <p>Source code ERP pertambangan terintegrasi — operasi, fleet, BBM, penjualan hingga accounting.</p>
            </div>
            <nav aria-label="Tautan komersial">
                <div class="font-semibold text-white mb-2">Jelajahi</div>
                <ul class="space-y-1.5">
                    <li><a href="/erp-mining" class="hover:text-white">ERP Mining</a></li>
                    <li><a href="/source-code-erp-tambang" class="hover:text-white">Source Code ERP Tambang</a></li>
                    <li><a href="/harga-erp-tambang" class="hover:text-white">Harga Mulai Rp12 Juta</a></li>
                    <li><a href="/cari-solusi" class="hover:text-white">Cari Solusi</a></li>
                </ul>
            </nav>
            <div>
                <div class="font-semibold text-white mb-2">Kontak</div>
                <p>WhatsApp: <a href="{{ \App\Services\WhatsappService::link('Halo, saya ingin konsultasi ERP Mining.') }}" class="text-amber-400 hover:text-amber-300">{{ \App\Services\WhatsappService::number() }}</a></p>
                <p class="mt-1">Senin–Jumat, jam kerja WIB.</p>
            </div>
        </div>
        <div class="border-t border-white/10 py-4 text-center text-xs">© {{ now()->year }} Mining ERP. Detail lisensi source code mengikuti penawaran/perjanjian.</div>
    </footer>
    @yield('sticky-cta')
</body>
</html>
