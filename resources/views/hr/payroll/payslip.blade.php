<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><title>Slip Gaji {{ $detail->employee?->name }}</title>
<style>
body { font-family: Arial; max-width: 600px; margin: 30px auto; color: #1e293b; }
h2 { margin: 0; } .hdr { border-bottom: 3px solid #f59e0b; padding-bottom: 10px; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-top: 8px; }
td { padding: 5px 8px; } .r { text-align: right; }
.net { background: #fef3c7; font-weight: bold; font-size: 15px; }
hr { border: none; border-top: 1px dashed #94a3b8; }
</style>
</head>
<body onload="window.print()">
<div class="hdr">
    <h2>SLIP GAJI</h2>
    <div>{{ $run->company?->name }} · Periode {{ $run->period }}</div>
</div>
<table>
<tr><td>Nama</td><td class="r">{{ $detail->employee?->name }}</td></tr>
<tr><td>NIP</td><td class="r">{{ $detail->employee?->code }}</td></tr>
<tr><td>No Payroll</td><td class="r">{{ $run->number }}</td></tr>
</table>
<hr>
<table>
<tr><td colspan="2"><strong>PENERIMAAN</strong></td></tr>
@forelse ($detail->components['earnings'] ?? [] as $e)
<tr><td>{{ $e['name'] }}</td><td class="r">Rp {{ number_format($e['amount'], 0, ',', '.') }}</td></tr>
@empty
<tr><td colspan="2">-</td></tr>
@endforelse
<tr><td><strong>Total Bruto</strong></td><td class="r"><strong>Rp {{ number_format($detail->total_earning, 0, ',', '.') }}</strong></td></tr>
</table>
<table>
<tr><td colspan="2"><strong>POTONGAN</strong></td></tr>
@forelse ($detail->components['deductions'] ?? [] as $d)
<tr><td>{{ $d['name'] }}</td><td class="r">Rp {{ number_format($d['amount'], 0, ',', '.') }}</td></tr>
@empty
<tr><td colspan="2">-</td></tr>
@endforelse
<tr class="net"><td>NETO DITERIMA</td><td class="r">Rp {{ number_format($detail->net_salary, 0, ',', '.') }}</td></tr>
</table>
<p style="font-size:11px;color:#64748b;margin-top:24px">Slip ini digenerate otomatis oleh {{ config('app.name') }} pada {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>