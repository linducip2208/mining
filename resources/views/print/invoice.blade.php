@extends('layouts.print')
@section('document')
<x-print.header title="INVOICE" :number="$invoice->number" :logo="$logo" :company="$company" />
<x-print.meta :items="['Tanggal Invoice' => \App\Support\DateFormatter::long($invoice->invoice_date), 'Customer' => $invoice->customer?->name, 'Referensi SO' => $invoice->salesOrder?->number, 'Jatuh Tempo' => \App\Support\DateFormatter::long($invoice->due_date)]" />
<table class="print-table"><thead><tr><th>Deskripsi</th><th>Qty</th><th>Unit</th><th>Harga</th><th class="numeric">Jumlah</th></tr></thead><tbody>
@foreach($invoice->items as $item)<tr><td>{{ $item->item?->name }}</td><td>{{ \App\Support\NumberFormatter::decimal($item->qty) }}</td><td>{{ $item->unit ?? 'Ton' }}</td><td>{{ \App\Support\NumberFormatter::money($item->unit_price ?? 0) }}</td><td class="numeric">{{ \App\Support\NumberFormatter::money($item->total_price) }}</td></tr>@endforeach
</tbody></table>
<x-print.summary :items="['Subtotal' => \App\Support\NumberFormatter::money($invoice->subtotal), 'Pajak' => \App\Support\NumberFormatter::money($invoice->tax_amount), 'Total' => \App\Support\NumberFormatter::money($invoice->total)]" />
<x-print.signature :items="['Disiapkan Oleh', 'Diperiksa Oleh', 'Disetujui Oleh']" />
@if($showQr)<div class="print-muted" style="margin-top:16px">Verifikasi dokumen: {{ url('/verify/'.\App\Services\DocumentVerificationService::token('invoice', $invoice->number)) }}</div>@endif
<x-print.footer />
@endsection
