{{-- Reusable WhatsApp CTA. Never hardcode wa.me links elsewhere. --}}
@props(['label' => 'Tanya via WhatsApp', 'position' => 'body', 'type' => 'general', 'page' => null, 'compact' => false])
@php
    use App\Services\WhatsappService;
    if ($page) {
        $href = WhatsappService::link(
            WhatsappService::defaultMessage($page->title, $page->intent),
            ['page' => $page->path, 'cluster' => $page->cluster, 'intent' => $page->intent]
        );
    } else {
        $href = WhatsappService::link('Halo, saya tertarik dengan Source Code ERP Mining mulai Rp12 juta. Saya ingin informasi lebih lanjut.');
    }
@endphp
<a href="{{ $href }}" target="_blank" rel="noopener"
    data-cta-position="{{ $position }}" data-cta-type="{{ $type }}" data-cta-page="{{ $page?->id }}"
    aria-label="{{ $label }} via WhatsApp 0812-9605-2010"
    @class([
        'inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition-colors',
        'min-h-[44px] px-4 text-sm' => $compact,
        'min-h-[48px] px-6 text-base' => ! $compact,
    ])>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.4-.7-2.9-1.2-4.7-4.1-4.9-4.3-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5s.8 1.9.8 2c.1.1.1.3 0 .5-.3.6-.6.8-.4 1.1.7 1.2 1.6 2 2.8 2.6.3.2.5.1.7-.1l.8-.9c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.4 0 .1 0 .7-.6 1.8Z"/></svg>
    {{ $label }}
</a>
