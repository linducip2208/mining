@extends('layouts.seo')

@section('content')
<nav aria-label="Breadcrumb" class="max-w-6xl mx-auto px-4 pt-5 text-xs text-slate-500">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach($breadcrumbs as $i => $crumb)
        <li class="flex items-center gap-1.5">
            @if($i > 0)<span aria-hidden="true">/</span>@endif
            @if($i < count($breadcrumbs) - 1)
            <a href="{{ $crumb['url'] }}" class="hover:text-amber-600">{{ $crumb['name'] }}</a>
            @else
            <span aria-current="page" class="text-slate-700 font-medium">{{ $crumb['name'] }}</span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>

<x-marketing.hero :page="$page" :hero="$content['hero']" :content="$content" />
<x-marketing.problem-solution :problems="$content['problems']" :solution="$content['solution']" />
<x-marketing.workflow :steps="$content['workflow']" />
<x-marketing.feature-grid :features="$content['features']" />
<x-marketing.industry-use-case :page="$page" />
<x-marketing.source-code-benefits :benefits="$content['benefits']" />
<x-marketing.price-card :page="$page" :price="$content['price']" />
<x-marketing.faq :faqs="$content['faqs']" />
<x-marketing.related-pages :related="$related" :hub="$hub" />

<section aria-labelledby="cta-final" class="max-w-6xl mx-auto px-4 mt-12">
    <div class="rounded-2xl bg-[#0f172a] text-white px-6 py-10 text-center">
        <h2 id="cta-final" class="text-2xl md:text-3xl font-extrabold tracking-tight">Diskusikan Kebutuhan ERP Tambang Anda</h2>
        <p class="mt-2 text-slate-300 text-sm md:text-base">Source code mulai {{ $content['price']['display'] }} ({{ $content['price']['short'] }}). Ceritakan workflow tambang Anda — kami petakan ke modul yang tepat.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <x-marketing.whatsapp-cta :page="$page" label="Konsultasi ERP Pertambangan" position="final" type="whatsapp" />
            <x-marketing.whatsapp-cta :page="$page" label="Tanya Harga Mulai Rp12 Juta" position="final" type="price" />
        </div>
    </div>
</section>
@endsection

@section('sticky-cta')
<x-marketing.sticky-mobile-cta :page="$page" />
@endsection
