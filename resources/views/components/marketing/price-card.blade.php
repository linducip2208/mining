{{-- Price card: fixed starting price, honest scope note. --}}
@props(['page', 'price'])
<section aria-labelledby="harga" class="max-w-6xl mx-auto px-4 mt-12">
    <h2 id="harga" class="text-2xl font-bold tracking-tight">Harga Source Code</h2>
    <div class="mt-4 max-w-md rounded-2xl border-2 border-amber-400 bg-white shadow-lg overflow-hidden">
        <div class="bg-[#0f172a] text-white px-6 py-4">
            <div class="text-xs font-bold tracking-widest uppercase text-amber-400">Source Code ERP Mining</div>
            <div class="mt-1 text-sm text-slate-300">Mulai dari</div>
            <div class="text-4xl font-extrabold">{{ $price['display'] }}</div>
        </div>
        <ul class="px-6 py-5 space-y-2.5 text-sm">
            @foreach(['Source Code', 'Mining ERP', 'Customizable', 'White-label', 'Self-hosted'] as $item)
            <li class="flex items-center gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-emerald-500 shrink-0" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                {{ $item }}
            </li>
            @endforeach
        </ul>
        <div class="px-6 pb-6">
            <x-marketing.whatsapp-cta :page="$page" label="Tanya Penawaran via WhatsApp" position="price" type="whatsapp" />
            <p class="mt-3 text-xs text-slate-500 leading-relaxed">{{ $price['note'] }}</p>
        </div>
    </div>
</section>
