@extends('layouts.print')
@section('document')
<x-print.header :title="$letter->type?->name ?? 'SURAT'" :number="$letter->number" :logo="$logo" :company="$company" />
<x-print.meta :items="['Nomor' => $letter->number ?? '-', 'Tanggal' => \App\Support\DateFormatter::long($letter->letter_date), 'Jenis' => $letter->type?->name, 'Status' => $letter->status]" />
<table class="print-table"><tbody>
<tr><th style="width:25%">Kepada Yth</th><td><strong>{{ $letter->recipient_name }}</strong>@if($letter->recipient_company)<br>{{ $letter->recipient_company }}@endif@if($letter->recipient_address)<br>{{ $letter->recipient_address }}@endif@if($letter->recipient_phone)<br>Tel: {{ $letter->recipient_phone }}@endif</td></tr>
<tr><th>Perihal</th><td><strong>{{ $letter->subject }}</strong></td></tr>
</tbody></table>
<div style="margin:16px 0; white-space:pre-line">{{ $letter->body }}</div>
@if($letter->notes)<p class="print-muted">Keterangan: {{ $letter->notes }}</p>@endif
<p class="print-muted">{{ $letter->company?->city ?? '' }}, {{ \App\Support\DateFormatter::long($letter->letter_date) }}<br>{{ $letter->company?->name }}</p>
<x-print.signature :items="['Dibuat Oleh', 'Disetujui Oleh', 'Ditandatangani Oleh']" />
<p class="print-muted">Dibuat: {{ $letter->creator?->name ?? '-' }} · Disetujui: {{ $letter->approver?->name ?? '-' }} · Ditandatangani: {{ $letter->signedBy?->name ?? '-' }} {{ $letter->signed_at?->format('d/m/Y') ?? '' }}</p>
<x-print.footer />
@endsection
