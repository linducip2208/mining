@props(['items' => []])
<div class="print-summary">@foreach($items as $label => $value)<div class="print-summary-row {{ $loop->last ? 'total' : '' }}"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>@endforeach</div>
