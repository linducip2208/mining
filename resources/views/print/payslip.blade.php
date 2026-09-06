@extends('layouts.print')
@section('document')
<x-print.header title="SLIP GAJI" :number="$run->number" :logo="$logo" :company="$company" />
<x-print.meta :items="['Periode' => $run->period, 'Karyawan' => $detail->employee?->name, 'NIK' => $detail->employee?->code, 'Departemen' => $detail->employee?->department?->name, 'Jabatan' => $detail->employee?->position]" />
<table class="print-table"><thead><tr><th>Komponen</th><th class="numeric">Penerimaan</th><th class="numeric">Potongan</th></tr></thead><tbody>
@foreach($detail->components['earnings'] ?? [] as $earning)<tr><td>{{ $earning['name'] }}</td><td class="numeric">{{ \App\Support\NumberFormatter::money($earning['amount']) }}</td><td class="numeric">—</td></tr>@endforeach
@foreach($detail->components['deductions'] ?? [] as $deduction)<tr><td>{{ $deduction['name'] }}</td><td class="numeric">—</td><td class="numeric">{{ \App\Support\NumberFormatter::money($deduction['amount']) }}</td></tr>@endforeach
</tbody></table>
<x-print.summary :items="['Total Bruto' => \App\Support\NumberFormatter::money($detail->total_earning), 'Total Potongan' => \App\Support\NumberFormatter::money($detail->total_deduction), 'NET SALARY' => \App\Support\NumberFormatter::money($detail->net_salary)]" />
<x-print.signature :items="['Karyawan', 'Payroll', 'Disetujui Oleh']" />
<x-print.footer />
@endsection
