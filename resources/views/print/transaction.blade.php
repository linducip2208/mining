@extends('layouts.print')
@section('document')
<x-print.header :title="$documentTitle" :number="$documentNumber" :logo="$logo" :company="$company" />
<x-print.meta :items="$fields" />
<h2 style="font-size:11pt;border-bottom:1px solid #9ca3af;padding-bottom:5px">Detail Dokumen</h2>
<table class="print-table"><tbody><tr><th>Nomor Dokumen</th><td>{{ $documentNumber }}</td></tr><tr><th>Jenis Dokumen</th><td>{{ $documentTitle }}</td></tr><tr><th>Status</th><td>{{ \App\Support\HumanLabel::label($record->status ?? '—') }}</td></tr></tbody></table>
<x-print.signature />
<x-print.footer />
@endsection
