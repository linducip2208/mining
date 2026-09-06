@php($footerText = \App\Services\BrandingService::get('document.footer_text', ''))
@php($appName = \App\Services\BrandingService::appName())
@php($printedAt = $printedAt ?? now())
@php($printedBy = $printedBy ?? auth()->user()?->name ?? 'Sistem')
@php($showPageNumber = \App\Services\BrandingService::get('document.show_page_number', true))
<footer class="print-footer">
    <span>{{ $footerText ?: $appName }}</span>
    <span>Dicetak {{ $printedAt->format('d/m/Y H:i') }} oleh {{ $printedBy }}
        @if($showPageNumber) Halaman 1 @endif
    </span>
</footer>
