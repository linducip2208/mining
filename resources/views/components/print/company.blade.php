@props(['company' => null, 'logo' => null])
@php($company = $company ?: \App\Services\BrandingService::companyProfile())
<div class="print-brand">@if($logo)<img src="{{ $logo }}" alt="Logo {{ $company['name'] }}">@endif<div><div class="print-company-name">{{ $company['legal_name'] ?: $company['name'] }}</div><div class="print-muted">{{ $company['address'] }}{{ $company['city'] ? ', '.$company['city'] : '' }}</div></div></div>
