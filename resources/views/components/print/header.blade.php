@props(['title' => null, 'number' => null, 'logo' => null, 'company' => null])
@php
    $company = $company ?: \App\Services\BrandingService::companyProfile();
    $logo = $logo ?: \App\Services\BrandingService::assetUrl('document.logo', \App\Services\BrandingService::assetUrl('branding.logo_main'));
    $showAddress = \App\Services\BrandingService::get('document.show_address', true);
    $showPhone = \App\Services\BrandingService::get('document.show_phone', true);
    $showEmail = \App\Services\BrandingService::get('document.show_email', true);
    $showNpwp = \App\Services\BrandingService::get('document.show_npwp', true);
    $headerText = \App\Services\BrandingService::get('document.header_text', '');
@endphp
<header class="print-header">
    <div class="print-brand">
        @if($logo)<img src="{{ $logo }}" alt="Logo {{ $company['name'] }}">@endif
        <div>
            <div class="print-company-name">{{ $company['legal_name'] ?: $company['name'] }}</div>
            @if($showAddress && ($company['address'] || $company['city']))<div class="print-muted">{{ $company['address'] }}{{ $company['city'] ? ', '.$company['city'] : '' }}{{ $company['province'] ? ', '.$company['province'] : '' }}</div>@endif
            @if($showPhone && $company['phone'])<div class="print-muted">Tel: {{ $company['phone'] }}</div>@endif
            @if($showEmail && $company['email'])<div class="print-muted">Email: {{ $company['email'] }}</div>@endif
            @if($showNpwp && $company['npwp'])<div class="print-muted">NPWP: {{ $company['npwp'] }}</div>@endif
            @if($headerText)<div class="print-muted">{{ $headerText }}</div>@endif
        </div>
    </div>
    <div class="print-title"><h1>{{ $title ?: 'DOKUMEN' }}</h1>@if($number)<div>{{ $number }}</div>@endif</div>
</header>
