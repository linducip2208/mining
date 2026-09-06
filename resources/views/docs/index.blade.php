@extends('layouts.docs')

@section('meta-title', 'Mining ERP Documentation')
@section('meta-desc', 'Panduan lengkap penggunaan Mining ERP untuk operasi tambang, fleet, inventory, sales, HR, maintenance, finance, dan management.')

@section('content')
@php
    use App\Docs\DocRegistry;
    $groups = DocRegistry::groups();
    $totalPages = count(DocRegistry::allPages());
    $quick = [
        ['Mulai dari Sini', 'Login, navigasi, dan konsep dasar dalam 10 menit.', '/docs/getting-started/navigasi', 'star'],
        ['Mine to Cash', 'Tambang → timbang → produksi → jual → kas.', '/docs/workflows/mine-to-cash', 'pickaxe'],
        ['Procure to Pay', 'PR → approval → PO → GRN → tagihan → bayar.', '/docs/workflows/procure-to-pay', 'cart'],
        ['Payroll to Accounting', 'Absensi → lembur → insentif → payroll → jurnal.', '/docs/workflows/payroll', 'wallet'],
        ['Maintenance Flow', 'WO, jadwal, downtime, dan biaya alat.', '/docs/workflows/maintenance', 'wrench'],
        ['FAQ', 'Jawaban cepat dan troubleshooting.', '/docs/faq/general', 'help'],
    ];
    $flows = [
        ['Mine to Cash', 'pickaxe', [
            ['Mining', '/docs/mining/activity'], ['Weighbridge', '/docs/weighbridge/tickets'],
            ['Production', '/docs/production/batches'], ['Stockpile', '/docs/stockpile/board'],
            ['Sales Order', '/docs/sales/sales-orders'], ['Delivery Order', '/docs/sales/delivery-orders'],
            ['Invoice', '/docs/sales/invoices'], ['Payment', '/docs/sales/payments'], ['Accounting', '/docs/finance/journals'],
        ]],
        ['Procure to Pay', 'cart', [
            ['PR', '/docs/procurement/purchase-requests'], ['Approval', '/docs/approval/center'],
            ['PO', '/docs/procurement/purchase-orders'], ['Goods Receipt', '/docs/procurement/goods-receipts'],
            ['Vendor Bill', '/docs/procurement/vendor-bills'], ['Payment', '/docs/sales/payments'],
        ]],
        ['Fuel to Cost', 'fuel', [
            ['Fuel Receipt', '/docs/fuel/receipts'], ['Tank', '/docs/fuel/dashboard'],
            ['Fuel Issue', '/docs/fuel/issues'], ['Equipment', '/docs/fleet/dashboard'],
            ['Cost', '/docs/cost/dashboard'], ['Cost/Ton', '/docs/cost/dashboard'],
        ]],
        ['Payroll', 'wallet', [
            ['Attendance', '/docs/hr/attendances'], ['Overtime', '/docs/hr/overtimes'],
            ['Incentive', '/docs/hr/incentives'], ['Payroll', '/docs/hr/payroll'],
            ['Payment', '/docs/hr/payroll'], ['Accounting', '/docs/finance/journals'],
        ]],
    ];
@endphp

{{-- HERO --}}
<section class="relative overflow-hidden rounded-2xl bg-navy-900 dark:bg-navy-800 text-white px-6 py-10 lg:px-12 lg:py-14" aria-labelledby="docsHeroTitle">
    <div class="absolute inset-0 opacity-20" aria-hidden="true" style="background-image: radial-gradient(circle at 85% 20%, #f59e0b 0, transparent 35%), radial-gradient(circle at 10% 90%, #f59e0b55 0, transparent 30%);"></div>
    <div class="relative max-w-2xl">
        <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-400">{{ $totalPages }} tutorial · screenshot nyata aplikasi</div>
        <h1 id="docsHeroTitle" class="mt-2 text-3xl lg:text-4xl font-bold tracking-tight">Mining ERP Documentation</h1>
        <p class="mt-3 text-slate-300 leading-relaxed">Panduan lengkap penggunaan Mining ERP untuk operasi tambang, fleet, inventory, sales, HR, maintenance, finance, dan management.</p>
        <form action="{{ route('docs.search') }}" method="GET" role="search" class="mt-6 flex gap-2 max-w-xl">
            <label for="heroSearch" class="sr-only">Cari dokumentasi</label>
            <input id="heroSearch" type="search" name="q" autocomplete="off" placeholder="Cari fitur, tutorial, menu, error…"
                class="flex-1 px-4 py-3 rounded-xl bg-white/10 border border-white/15 text-white placeholder-slate-400 text-sm focus:border-amber-400 focus:bg-white/15 outline-none">
            <button class="px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Cari</button>
        </form>
        <p class="mt-2 text-xs text-slate-500">Tekan <kbd class="px-1.5 py-0.5 rounded border border-white/15 font-sans">Ctrl K</kbd> di mana saja untuk pencarian cepat.</p>
    </div>
</section>

{{-- QUICK ACTIONS --}}
<section aria-label="Aksi cepat" class="mt-6 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
    @foreach ($quick as [$t, $d, $u, $ic])
    <a href="{{ $u }}" class="group bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-4 hover:border-amber-400 hover:shadow-pop transition">
        <span class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center"><x-ui.icon :name="$ic" class="w-5 h-5" /></span>
        <span class="block font-semibold text-sm mt-2.5 text-slate-800 dark:text-slate-100 group-hover:text-amber-700 dark:group-hover:text-amber-300">{{ $t }}</span>
        <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ $d }}</span>
    </a>
    @endforeach
</section>

{{-- MODULE GROUPS --}}
@foreach ($groups as $gName => $gSections)
    @php $vis = collect($gSections)->filter(fn ($s) => isset($sections[$s])); @endphp
    @if ($vis->isEmpty()) @continue @endif
    <section aria-label="{{ $gName }}" class="mt-10">
        <div class="flex items-baseline justify-between gap-3">
            <h2 class="text-lg font-bold tracking-tight text-slate-800 dark:text-slate-100">{{ $gName }}</h2>
            <span class="text-xs text-slate-400">{{ $vis->sum(fn ($s) => count($sections[$s]['pages'])) }} tutorial</span>
        </div>
        <div class="mt-3 grid md:grid-cols-2 gap-4">
            @foreach ($vis as $sSlug)
            @php
                $s = $sections[$sSlug];
                $meta = DocRegistry::sectionMeta($sSlug);
                $st = DocRegistry::sectionStats($sSlug, $s);
            @endphp
            <article class="bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-5 hover:shadow-pop transition">
                <div class="flex items-start gap-3">
                    <span class="flex-none w-10 h-10 rounded-xl bg-navy-900 dark:bg-white/5 text-amber-400 flex items-center justify-center"><x-ui.icon :name="$meta['icon']" class="w-5 h-5" /></span>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-[15px] text-slate-800 dark:text-slate-100">{{ $s['title'] }}</h3>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ $meta['blurb'] }}</p>
                    </div>
                    <span class="flex-none text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-full {{ $st['complete'] ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-300' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300' }}">
                        {{ $st['complete'] ? 'Lengkap' : 'Berkembang' }}
                    </span>
                </div>
                <div class="mt-2 text-xs text-slate-400">{{ $st['tutorials'] }} Tutorial · {{ $st['screenshots'] }} Screenshot</div>
                <div class="mt-2.5 pt-3 border-t border-slate-100 dark:border-slate-700/60">
                    <div class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">Populer</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($st['popular'] as $pSlug)
                        <a href="/docs/{{ $sSlug }}/{{ $pSlug }}" class="text-xs px-2.5 py-1 rounded-full bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:bg-amber-100 hover:text-amber-800 dark:hover:bg-amber-500/15 dark:hover:text-amber-300">{{ $s['pages'][$pSlug]['title'] }}</a>
                        @endforeach
                        @if (count($s['pages']) > 4)
                        <a href="/docs/{{ $sSlug }}/{{ array_key_first($s['pages']) }}" class="text-xs px-2.5 py-1 text-amber-600 dark:text-amber-400 hover:underline">+{{ count($s['pages']) - 4 }} lainnya →</a>
                        @endif
                    </div>
                </div>
            </article>
            @endforeach
        </div>
    </section>
@endforeach

{{-- WORKFLOWS --}}
<section aria-label="Alur kerja end-to-end" class="mt-10 rounded-2xl bg-slate-200/50 dark:bg-white/[0.03] border border-slate-200 dark:border-slate-700/60 p-5 lg:p-7">
    <h2 class="text-lg font-bold tracking-tight text-slate-800 dark:text-slate-100">Alur Kerja End-to-End</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ikuti proses bisnis dari awal sampai akhir — setiap tahap tertaut ke tutorialnya.</p>
    <div class="mt-4 grid lg:grid-cols-2 gap-4">
        @foreach ($flows as [$fname, $fic, $steps])
        <div class="bg-white dark:bg-navy-800 rounded-card border border-slate-200 dark:border-slate-700/60 shadow-card p-4">
            <div class="flex items-center gap-2 font-semibold text-sm text-slate-800 dark:text-slate-100">
                <x-ui.icon :name="$fic" class="w-4 h-4 text-amber-500" />{{ $fname }}
            </div>
            <ol class="flow-track mt-3 pb-1 nice-scroll" aria-label="Tahapan {{ $fname }}">
                @foreach ($steps as $i => [$label, $url])
                <li class="flow-node flex-none">
                    <a href="{{ $url }}" class="block rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2.5 text-center hover:border-amber-400 hover:shadow-sm transition h-full">
                        <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-400">TAHAP {{ $i + 1 }}</span>
                        <span class="block text-[13px] font-semibold text-slate-700 dark:text-slate-200 mt-0.5">{{ $label }}</span>
                    </a>
                </li>
                @if (!$loop->last)
                <li class="flow-arrow px-1 text-slate-300 dark:text-slate-600" aria-hidden="true"><x-ui.icon name="chevron-right" class="w-4 h-4" /></li>
                @endif
                @endforeach
            </ol>
        </div>
        @endforeach
    </div>
</section>
@endsection
