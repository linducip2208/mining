@php
    $appName = \App\Services\BrandingService::appName();
    $company = \App\Services\BrandingService::companyName();
    $tagline = \App\Services\BrandingService::tagline();
    $loginTitle = \App\Services\BrandingService::get('login.title', 'Selamat datang kembali');
    $loginSubtitle = \App\Services\BrandingService::get('login.subtitle', 'Masuk untuk melanjutkan ke Mining ERP.');
    $loginFooter = \App\Services\BrandingService::get('login.footer_text', 'Akses aman untuk tim operasional.');
    $showCompany = filter_var(\App\Services\BrandingService::get('login.show_company_name', true), FILTER_VALIDATE_BOOLEAN);
    $showTagline = filter_var(\App\Services\BrandingService::get('login.show_tagline', true), FILTER_VALIDATE_BOOLEAN);
    $logo = \App\Services\BrandingService::assetUrl('login.logo') ?: \App\Services\BrandingService::assetUrl('branding.logo_login') ?: \App\Services\BrandingService::assetUrl('branding.logo_main');
    $loginBackground = \App\Services\BrandingService::assetUrl('login.background_image');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $appName }} — Masuk</title>
    @include('layouts.partials.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <main class="min-h-screen grid lg:grid-cols-2">
        <section class="relative hidden lg:flex overflow-hidden bg-[#101923] p-12 xl:p-16 flex-col justify-between text-white" @if($loginBackground) style="background-image:linear-gradient(135deg,rgba(15,23,42,.96),rgba(15,23,42,.82)),url('{{ $loginBackground }}');background-size:cover;background-position:center" @endif>
            <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-amber-400/15 blur-3xl"></div><div class="absolute -left-20 bottom-0 w-80 h-80 rounded-full bg-sky-400/10 blur-3xl"></div>
            <div class="relative flex items-center gap-3"><div class="w-11 h-11 rounded-xl bg-amber-400 text-[#101923] flex items-center justify-center font-black text-lg overflow-hidden">@if($logo)<img src="{{ $logo }}" alt="{{ $appName }}" class="w-full h-full object-contain">@else{{ mb_substr($appName, 0, 1) }}@endif</div><div><div class="font-bold text-lg">{{ $appName }}</div>@if($showCompany)<div class="text-xs text-slate-400">{{ $company }}</div>@endif</div></div>
            <div class="relative max-w-xl"><div class="mb-5 text-xs font-semibold uppercase tracking-[.18em] text-amber-300">Enterprise operations platform</div><h1 class="text-5xl xl:text-6xl font-bold tracking-[-.05em] leading-[1.05]">Satu pusat kendali untuk operasi tambang.</h1><p class="mt-6 text-lg leading-8 text-slate-300 max-w-lg"><span>@if($showTagline){{ $tagline }}. @endif</span> Pantau produksi, biaya, risiko, dan keputusan harian dalam alur kerja yang terukur.</p><div class="mt-9 grid grid-cols-3 gap-3 max-w-xl">@foreach([['shield','Kontrol'],['chart','Visibilitas'],['pickaxe','Operasi']] as [$icon,$label])<div class="rounded-xl border border-white/10 bg-white/5 p-4"><x-ui.icon :name="$icon" class="w-5 h-5 text-amber-300 mb-5" /><div class="text-sm font-semibold">{{ $label }}</div><div class="mt-1 text-[11px] text-slate-400">Terintegrasi</div></div>@endforeach</div></div>
            <div class="relative text-xs text-slate-500">{{ \App\Services\BrandingService::copyright() }}</div>
        </section>
        <section class="flex items-center justify-center p-6 sm:p-10 lg:p-16">
            <div class="w-full max-w-md">
                <div class="lg:hidden flex items-center gap-3 mb-12"><div class="w-10 h-10 rounded-xl bg-amber-400 text-[#101923] flex items-center justify-center font-black">@if($logo)<img src="{{ $logo }}" alt="{{ $appName }}" class="w-full h-full object-contain">@else{{ mb_substr($appName, 0, 1) }}@endif</div><div><div class="font-bold">{{ $appName }}</div><div class="text-xs text-slate-500">{{ $company }}</div></div></div>
                <div class="mb-8"><div class="text-xs font-semibold uppercase tracking-[.15em] text-amber-600 mb-3">Secure workspace</div><h2 class="text-4xl font-bold tracking-[-.04em] text-slate-900">Masuk</h2><p class="mt-2 text-sm text-slate-500">{{ $loginTitle }}. {{ $loginSubtitle }}</p></div>
                @if ($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form method="POST" action="{{ route('login') }}" class="space-y-5">@csrf
                    <div><label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email atau Username</label><input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full h-12 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-100"></div>
                    <div><div class="flex items-center justify-between mb-2"><label for="password" class="text-sm font-semibold text-slate-700">Password</label>@if(Route::has('password.request'))<a href="{{ route('password.request') }}" class="text-xs font-semibold text-amber-700 hover:underline">Lupa password?</a>@endif</div><input id="password" type="password" name="password" required autocomplete="current-password" class="w-full h-12 rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-100"></div>
                    <label class="flex items-center gap-2 text-sm text-slate-500"><input type="checkbox" name="remember" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400"> Ingat perangkat ini</label>
                    <button type="submit" class="w-full h-12 rounded-xl bg-slate-900 text-white text-sm font-bold shadow-lg shadow-slate-900/15 hover:bg-amber-600 transition">Masuk ke Workspace</button>
                </form>
                <div class="flex items-center gap-3 my-7 text-[11px] text-slate-400"><span class="h-px flex-1 bg-slate-200"></span>atau<span class="h-px flex-1 bg-slate-200"></span></div>
                <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="font-semibold text-sm text-slate-800 mb-3">Akun demo</div><div class="space-y-1.5 text-[11px] font-mono text-slate-500"><div><strong class="text-slate-700">Super Admin:</strong> admin@miningerp.local / Admin!2345</div><div><strong class="text-slate-700">Manager:</strong> gm@miningerp.local / Demo!2345</div><div><strong class="text-slate-700">Finance:</strong> fin.mgr@miningerp.local / Demo!2345</div><div><strong class="text-slate-700">Operator:</strong> wb.op@miningerp.local / Demo!2345</div></div></div>
                <p class="mt-6 text-center text-xs text-slate-400">{{ $loginFooter }}</p>
            </div>
        </section>
    </main>
</body>
</html>
