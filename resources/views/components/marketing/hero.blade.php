{{-- Hero: H1 unik + subheadline + harga + CTA. --}}
@props(['page', 'hero', 'content' => []])
<section class="bg-[#0f172a] text-white overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 pt-12 pb-10 md:pt-16 md:pb-14 grid gap-8 md:grid-cols-[1.4fr_1fr] items-center">
        <div class="min-w-0">
            <p class="text-xs font-bold tracking-[.15em] uppercase text-amber-400">{{ $hero['eyebrow'] }}</p>
            <h1 class="mt-3 text-3xl md:text-[2.75rem] md:leading-[1.15] font-extrabold tracking-tight text-balance">{{ $page->h1 }}</h1>
            <p class="mt-4 text-slate-300 text-base md:text-lg leading-relaxed max-w-2xl">{{ $hero['subheadline'] }}</p>
            <p class="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-400/15 border border-amber-400/40 text-amber-300 font-bold px-4 py-2 text-sm md:text-base">{{ $hero['price_line'] }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <x-marketing.whatsapp-cta :page="$page" label="Tanya Source Code ERP Mining" position="hero" type="whatsapp" />
                <a href="/cari-solusi" class="inline-flex items-center justify-center min-h-[48px] px-6 rounded-xl border border-white/25 text-white font-semibold hover:bg-white/10">Minta Demo</a>
            </div>
            <p class="mt-3 text-xs text-slate-400">Respons jam kerja · WhatsApp {{ \App\Services\WhatsappService::displayNumber() }}</p>
        </div>
        <div class="hidden md:block">
            <div class="rounded-2xl bg-white/5 border border-white/10 p-6">
                <div class="text-xs font-bold tracking-widest uppercase text-slate-400">Alur terintegrasi</div>
                <ol class="mt-3 space-y-2 text-sm">
                    @foreach(array_slice($content['workflow'] ?? [], 0, 8) as $i => $step)
                    <li class="flex items-center gap-3">
                        <span class="w-6 h-6 shrink-0 rounded-full bg-amber-400 text-[#0f172a] text-xs font-bold grid place-items-center">{{ $i + 1 }}</span>
                        <span class="text-slate-200">{{ $step }}</span>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</section>
