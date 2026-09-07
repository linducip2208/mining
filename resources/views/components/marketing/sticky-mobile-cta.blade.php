{{-- Mobile sticky CTA: WhatsApp + Demo, safe-area aware. --}}
@props(['page'])
<div class="fixed bottom-0 inset-x-0 z-40 sm:hidden bg-white/95 backdrop-blur border-t border-slate-200 px-4 pt-2" style="padding-bottom: calc(.5rem + env(safe-area-inset-bottom));">
    <div class="grid grid-cols-2 gap-2">
        <x-marketing.whatsapp-cta :page="$page" label="WhatsApp" position="sticky" type="whatsapp" compact="1" />
        <a href="/cari-solusi" class="inline-flex items-center justify-center gap-2 rounded-xl min-h-[44px] px-4 text-sm bg-[#0f172a] text-white font-semibold" aria-label="Minta demo ERP tambang">Minta Demo</a>
    </div>
</div>
