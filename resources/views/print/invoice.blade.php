@extends('layouts.print')
@section('document')
<x-print.header title="INVOICE" :number="$invoice->number" :logo="$logo" :company="$company" />
<x-print.meta :items="['Nomor Invoice' => $invoice->number, 'Tanggal' => \App\Support\DateFormatter::long($invoice->invoice_date), 'Jatuh Tempo' => \App\Support\DateFormatter::long($invoice->due_date), 'Termin' => $invoice->paymentTerm?->name ?? '-', 'Referensi SO' => $invoice->salesOrder?->number ?? '-', 'Status' => $invoice->status]" />
<table class="print-table"><tbody>
<tr><th style="width:50%">Ditagihkan Kepada</th><th>Untuk Pembayaran</th></tr>
<tr><td><strong>{{ $invoice->customer?->name }}</strong><br>{{ $invoice->customer?->address }} {{ $invoice->customer?->city }}<br>Tel: {{ $invoice->customer?->phone }}<br>NPWP: {{ $invoice->customer?->npwp ?? '-' }}</td><td>Penjualan produk tambang sesuai rincian di bawah.<br>Term: {{ $invoice->paymentTerm?->name ?? '-' }}</td></tr>
</tbody></table>
<table class="print-table"><thead><tr><th>No</th><th>Produk / Deskripsi</th><th>Qty / Kubikasi</th><th>Unit</th><th>Harga Satuan</th><th class="numeric">Jumlah</th></tr></thead><tbody>
@foreach($invoice->items as $i => $item)<tr><td>{{ $i + 1 }}</td><td>{{ $item->item?->name }}</td><td>{{ \App\Support\NumberFormatter::decimal($item->qty) }}</td><td>{{ $item->item?->unit?->code ?? 'Ton' }}</td><td>{{ \App\Support\NumberFormatter::money($item->unit_price ?? 0) }}</td><td class="numeric">{{ \App\Support\NumberFormatter::money($item->total_price) }}</td></tr>@endforeach
</tbody></table>
<x-print.summary :items="['Subtotal' => \App\Support\NumberFormatter::money($invoice->subtotal), 'Diskon' => \App\Support\NumberFormatter::money($invoice->discount ?? 0), 'PPN' => \App\Support\NumberFormatter::money($invoice->tax_amount), 'Total' => \App\Support\NumberFormatter::money($invoice->total)]" />
<p><em>Terbilang: {{ \App\Services\NumberToWordsService::rupiah((float) $invoice->total) }}</em></p>
<table class="print-table"><tbody>
<tr><th style="width:50%">Sistem Pembayaran</th><th>Bank Perusahaan</th></tr>
<tr><td>Transfer ke rekening perusahaan di bawah.<br>Konfirmasi setelah pembayaran.</td><td>@if($bank ?? null)<strong>{{ $bank->bank_name }}</strong><br>No. Rekening: {{ $bank->account_no }}<br>A.n.: {{ $bank->name }}@else - @endif</td></tr>
</tbody></table>
<p class="print-muted">{{ $invoice->company?->city ?? '' }}, {{ \App\Support\DateFormatter::long($invoice->invoice_date) }}<br>{{ $invoice->company?->name }}</p>
<x-print.signature :items="['Disiapkan Oleh', 'Diperiksa Oleh', 'Disetujui Oleh']" />
<p class="print-muted">Disetujui oleh: {{ $invoice->approvedBy?->name ?? '-' }} · Dicetak oleh: {{ $printedBy ?? '-' }} · {{ now()->format('d/m/Y H:i') }}</p>
@if($showQr)<div class="print-muted" style="margin-top:16px">Verifikasi dokumen: {{ url('/verify/'.\App\Services\DocumentVerificationService::token('invoice', $invoice->number)) }}</div>@endif
<x-print.footer />
@endsection
