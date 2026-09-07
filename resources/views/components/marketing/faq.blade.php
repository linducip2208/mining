{{-- FAQ (matches FAQPage schema). --}}
@props(['faqs'])
@if($faqs !== [])
<section aria-labelledby="faq" class="max-w-6xl mx-auto px-4 mt-12">
    <h2 id="faq" class="text-2xl font-bold tracking-tight">Pertanyaan Umum</h2>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach($faqs as $faq)
        <details class="rounded-xl border border-slate-200 bg-white px-5 py-4 group">
            <summary class="font-semibold cursor-pointer min-h-[44px] flex items-center list-none [&::-webkit-details-marker]:hidden">
                <span class="flex-1">{{ $faq['q'] }}</span>
                <span class="ml-3 text-amber-500 font-bold group-open:rotate-45 transition-transform" aria-hidden="true">+</span>
            </summary>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $faq['a'] }}</p>
        </details>
        @endforeach
    </div>
</section>
@endif
