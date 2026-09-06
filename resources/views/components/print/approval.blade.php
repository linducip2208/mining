@props(['approver' => null, 'date' => null, 'status' => null])
@if($approver || $status)<div class="print-muted" style="margin-top:12px"><strong>Persetujuan</strong><br>Status: {{ $status ?: '—' }}@if($approver)<br>Disetujui oleh: {{ $approver }}@endif @if($date)<br>Tanggal: {{ $date }}@endif</div>@endif
