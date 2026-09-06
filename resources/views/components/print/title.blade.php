@props(['title', 'number' => null])
<div class="print-title"><h1>{{ $title }}</h1>@if($number)<div>{{ $number }}</div>@endif</div>
