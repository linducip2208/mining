@props(['items' => ['Dibuat Oleh', 'Diperiksa Oleh', 'Disetujui Oleh'], 'showSignature' => null, 'signatureName' => null, 'signatureTitle' => null])
@php($showSignature = $showSignature ?? (bool) \App\Services\BrandingService::get('document.show_signature', true))
@php($signatureName = $signatureName ?? \App\Services\BrandingService::get('document.signature_name', ''))
@php($signatureTitle = $signatureTitle ?? \App\Services\BrandingService::get('document.signature_title', ''))
@if($showSignature)<div class="print-signatures">@foreach($items as $item)<div class="print-signature"><div>{{ $item }}</div><div class="print-signature-line"></div><strong>{{ $signatureName ?: '—' }}</strong><div class="print-muted">{{ $signatureTitle }}</div></div>@endforeach</div>@endif
