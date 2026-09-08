@extends('layouts.print')
@section('document')
<x-print.header title="KWITANSI" :number="$receipt->number" :logo="$logo" :company="$company" />
<x-print.meta :items="['Nomor Kwitansi' => $receipt->number, 'Tanggal' => \App\Support\DateFormatter::long($receipt->receipt_date), 'Referensi Payment' => $receipt->payment?->number ?? '-', 'Metode' => $receipt->payment_method]" />
<table class="print-table"><tbody>
<tr><th style="width:35%">Telah Diterima Dari</th><td><strong>{{ $receipt->payer_name }}</strong>@if($receipt->customer)<br>{{ $receipt->customer->address }} {{ $receipt->customer->city }}@endif</td></tr>
<tr><th>Untuk Pembayaran</th><td>{{ $receipt->description }}</td></tr>
<tr><th>Jumlah</th><td><strong>{{ \App\Support\NumberFormatter::money($receipt->amount) }}</strong></td></tr>
<tr><th>Terbilang</th><td><em>{{ $terbilang }}</em></td></tr>
<tr><th>Bank / Kas</th><td>{{ $receipt->cashAccount?->bank_name }} {{ $receipt->cashAccount?->account_no }} {{ $receipt->reference_no ? '(Ref: ' . $receipt->reference_no . ')' : '' }}</td></tr>
</tbody></table>
@if($receipt->payment && $receipt->payment->allocations->isNotEmpty())
<p class="print-section-title">Alokasi Pembayaran</p>
<table class="print-table">
<thead><tr><th>Invoice</th><th>Tanggal Invoice</th><th style="text-align:right">Jumlah Dialokasikan</th></tr></thead>
<tbody>
@foreach($receipt->payment->allocations as $allocation)
<tr>
    <td>{{ $allocation->invoice?->number ?? '-' }}</td>
    <td>{{ $allocation->invoice?->invoice_date?->format('d/m/Y') ?? '-' }}</td>
    <td style="text-align:right">{{ \App\Support\NumberFormatter::money($allocation->amount) }}</td>
</tr>
@endforeach
</tbody>
</table>
@elseif($receipt->invoice)
<p class="print-muted">Referensi Invoice: {{ $receipt->invoice->number }}</p>
@endif
<p class="print-muted">{{ $receipt->company?->city ?? '' }}, {{ \App\Support\DateFormatter::long($receipt->receipt_date) }}<br>{{ $receipt->company?->name }}</p>
<x-print.signature :items="['Penerima', 'Penyetor']" />
<p class="print-muted">Dibuat oleh: {{ $receipt->creator?->name ?? '-' }} · Disetujui: {{ $receipt->approver?->name ?? '-' }} · Dicetak: {{ $receipt->printed_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</p>
@if(!empty($reprint))
<p style="color:#b91c1c;font-weight:700;border:1px solid #b91c1c;display:inline-block;padding:2px 8px;">*** SALINAN / REPRINT ***</p>
@endif
<x-print.footer />
@endsection
