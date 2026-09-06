@props(['items' => []])
<dl class="print-meta">@foreach($items as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>@endforeach</dl>
