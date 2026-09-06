<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Services\BrandingService::appName() }}@yield('title')</title>
        <link rel="icon" href="{{ \App\Services\BrandingService::assetUrl('branding.favicon') ?? url('/favicon.ico') }}">
        <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛏️</text></svg>">
        <script>
            try {
                if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
            } catch (e) {}
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 dark:text-slate-200 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-100 dark:bg-navy-950 relative">
            <div class="absolute inset-0 pointer-events-none opacity-[0.07] dark:opacity-[0.12]" aria-hidden="true" style="background-image: radial-gradient(circle at 80% 15%, #f59e0b 0, transparent 40%), radial-gradient(circle at 12% 85%, #f59e0b66 0, transparent 35%);"></div>
            <div class="relative">
                @php
                    $guestLogo = \App\Services\BrandingService::assetUrl('branding.logo_main');
                @endphp
                <a href="/" class="flex items-center gap-3" aria-label="Beranda {{ \App\Services\BrandingService::appName() }}">
                    <span class="w-11 h-11 rounded-xl bg-amber-500 flex items-center justify-center font-black text-xl text-slate-900 overflow-hidden">@if($guestLogo)<img src="{{ $guestLogo }}" alt="{{ \App\Services\BrandingService::appName() }}" class="w-full h-full object-contain bg-white">@else{{ mb_substr(\App\Services\BrandingService::appName(), 0, 1) }}@endif</span>
                    <span>
                        <span class="block font-bold tracking-wide text-slate-800 dark:text-slate-100">{{ \App\Services\BrandingService::appName() }}</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">{{ \App\Services\BrandingService::tagline() }}</span>
                    </span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white dark:bg-navy-800 shadow-card rounded-card border border-slate-200 dark:border-slate-700/60 relative">
                {{ $slot }}
            </div>

            <p class="relative mt-6 text-xs text-slate-400">© {{ now()->year }} Mining ERP · <a href="/docs" class="hover:text-amber-600">Dokumentasi</a></p>
        </div>
    </body>
</html>
