<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tiket Timbangan {{ $ticket->ticket_no }}</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 40px auto; max-width: 420px; }
        .bordered { border: 1px solid #000; padding: 12px; }
        .center { text-align: center; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        .big { font-size: 22px; font-weight: bold; }
        hr { border: none; border-top: 1px dashed #000; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <div class="center"><strong>{{ $ticket->weighbridge?->name ?? 'TIMBANGAN' }}</strong><br>{{ config('app.name') }}<br><small>{{ $ticket->weighbridge?->site?->name }}</small></div>
    <hr>
    <div class="row"><span>NO. TIKET</span><strong>{{ $ticket->ticket_no }}</strong></div>
    <div class="row"><span>TANGGAL</span><span>{{ $ticket->second_weigh_at?->format('d/m/Y H:i') ?? $ticket->first_weigh_at?->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>NOPOL</span><span>{{ $ticket->vehicle_plate }}</span></div>
    <div class="row"><span>SOPIR</span><span>{{ $ticket->driver_name ?? '-' }}</span></div>
    <div class="row"><span>CUSTOMER</span><span>{{ $ticket->customer?->name ?? $ticket->supplier?->name ?? '-' }}</span></div>
    <div class="row"><span>MATERIAL</span><span>{{ $ticket->item?->name ?? '-' }}</span></div>
    <hr>
    <div class="row"><span>GROSS</span><span>{{ number_format($ticket->gross, 0) }} kg</span></div>
    <div class="row"><span>TARE</span><span>{{ number_format($ticket->tare, 0) }} kg</span></div>
    <div class="bordered center" style="margin:8px 0"><small>NETTO</small><br><span class="big">{{ number_format($ticket->net, 0) }} kg</span></div>
    <div class="row"><span>KALIBRASI</span><span>{{ $ticket->calibration_version ?? '-' }}</span></div>
    <div class="row"><span>OPERATOR</span><span>{{ $ticket->operator?->name }}</span></div>
    <hr>
    <p class="center" style="font-size:11px">Dicetak {{ now()->format('d/m/Y H:i') }} · Cetak ke-{{ $ticket->reprint_count }} · Tiket ini bukan bukti pembayaran</p>
    <div class="center"><button onclick="window.print()" style="padding:8px 20px">Cetak</button></div>
</body>
</html>