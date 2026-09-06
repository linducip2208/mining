<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>Tiket Timbangan {{ $ticket->ticket_no }}</title>
<style>
@page{size:{{ $paperSize ?? '80mm' }} auto;margin:0}*{box-sizing:border-box}body{width:{{ ($paperSize ?? '80mm') === '58mm' ? '58mm' : '80mm' }};margin:0;padding:4mm 3mm;font:12px/1.35 Arial,sans-serif;color:#111}.center{text-align:center}.brand{font-size:15px;font-weight:800}.muted{color:#555;font-size:10px}.rule{border-top:1px dashed #111;margin:8px 0}.title{font-size:15px;font-weight:800;letter-spacing:.08em}.row{display:flex;justify-content:space-between;gap:8px}.row span:first-child{color:#555}.weight{font-size:14px;font-weight:700}.net{font-size:23px;font-weight:900}.qr{margin:8px auto;width:70px;height:70px;border:1px solid #111;display:grid;place-items:center;font-size:8px}
</style></head>
<body>
<div class="center"><div class="brand">{{ $company['name'] ?? $appName ?? 'Mining ERP' }}</div><div class="muted">{{ $company['address'] ?? '' }}</div><div class="muted">{{ $company['phone'] ?? '' }}</div></div>
<div class="rule"></div><div class="center title">TIKET TIMBANG</div>
<div class="rule"></div>
<div class="row"><span>Nomor</span><strong>{{ $ticket->ticket_no }}</strong></div>
<div class="row"><span>Tanggal</span><span>{{ $ticket->second_weigh_at?->format('d/m/Y H:i') ?? $ticket->first_weigh_at?->format('d/m/Y H:i') }}</span></div>
<div class="row"><span>Kendaraan</span><strong>{{ $ticket->vehicle_plate ?: '—' }}</strong></div>
<div class="row"><span>Driver</span><span>{{ $ticket->driver_name ?: '—' }}</span></div>
<div class="row"><span>Produk</span><span>{{ $ticket->item?->name ?: '—' }}</span></div>
<div class="row"><span>Site</span><span>{{ $ticket->site?->name ?: $ticket->company?->name ?: '—' }}</span></div>
<div class="rule"></div>
<div class="row"><span>GROSS</span><span class="weight">{{ number_format($ticket->gross, 0, ',', '.') }} kg</span></div>
<div class="row"><span>TARE</span><span class="weight">{{ number_format($ticket->tare, 0, ',', '.') }} kg</span></div>
<div class="row"><span>NET</span><span class="net">{{ number_format($ticket->net, 0, ',', '.') }} kg</span></div>
<div class="rule"></div><div class="row"><span>Operator</span><span>{{ $ticket->operator?->name ?: '—' }}</span></div>
<div class="qr">QR<br>{{ $ticket->ticket_no }}</div>
<div class="center muted">Simpan tiket ini sebagai bukti penimbangan.</div>
</body></html>
