@extends('layouts.print')
@section('document')
<x-print.header title="TIKET TIMBANGAN" :number="$ticket->ticket_no" :logo="$logo" :company="$company" />
<x-print.meta :items="['Tanggal / Waktu' => \App\Support\DateFormatter::long($ticket->second_weigh_at ?? $ticket->first_weigh_at), 'Kendaraan' => $ticket->vehicle_plate, 'Pengemudi' => $ticket->driver_name, 'Customer / Supplier' => $ticket->customer?->name ?? $ticket->supplier?->name, 'Material' => $ticket->item?->name, 'Sumber' => $ticket->source ?? null, 'Tujuan' => $ticket->destination ?? null, 'Operator' => $ticket->operator?->name]" />
<table class="print-table"><tbody><tr><th>Berat Kotor</th><td>{{ \App\Support\NumberFormatter::decimal($ticket->gross, 0) }} kg</td></tr><tr><th>Berat Tara</th><td>{{ \App\Support\NumberFormatter::decimal($ticket->tare, 0) }} kg</td></tr><tr><th>NET WEIGHT</th><td><strong style="font-size:16pt">{{ \App\Support\NumberFormatter::decimal($ticket->net, 0) }} kg</strong></td></tr></tbody></table>
<x-print.signature :items="['Operator Timbangan', 'Pengemudi', 'Penerima']" />
<x-print.footer />
@endsection
