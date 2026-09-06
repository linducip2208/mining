@extends('layouts.print')
@section('document')
<x-print.header :title="$reportTitle" :logo="$logo" :company="$company" />
<x-print.meta :items="$filters" />
@if($summary)<x-print.summary :items="$summary" />@endif
<table class="print-table"><thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($row as $cell)<td>{{ $cell ?: '—' }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headers) }}">Tidak ada data pada periode yang dipilih.</td></tr>@endforelse
</tbody></table>
<x-print.footer />
@endsection
