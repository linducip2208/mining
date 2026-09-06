<?php

namespace App\Services;

use App\Models\PrinterDevice;
use App\Models\PrintJob;
use App\Models\Setting;
use Illuminate\Support\Str;

final class PrintJobService
{
    public function __construct(private readonly PrinterRoutingService $routing) {}

    public function queue(string $documentType, int $documentId, ?PrinterDevice $printer = null, array $payload = [], ?int $userId = null, ?string $idempotencyKey = null, int $copies = 1): PrintJob
    {
        $idempotencyKey ??= $documentType.':'.$documentId.':'.Str::uuid();
        $existing = PrintJob::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return PrintJob::create([
            'uuid' => (string) Str::uuid(),
            'idempotency_key' => $idempotencyKey,
            'document_type' => strtoupper($documentType),
            'document_id' => $documentId,
            'printer_device_id' => $printer?->id,
            'workstation' => $printer?->workstation,
            'copies' => max(1, min(3, $copies)),
            'status' => 'QUEUED',
            'payload' => $payload,
            'requested_by' => $userId,
            'requested_at' => now(),
        ]);
    }

    public function queueWeighbridge($ticket, ?int $userId = null): PrintJob
    {
        $printer = $this->routing->resolve('WEIGHBRIDGE_TICKET', $ticket->company_id, $ticket->site_id);
        $thermalSize = $printer?->paper_size ?: Setting::get('printer.thermal_width', '80mm');
        $ticket->load(['weighbridge', 'customer', 'supplier', 'item', 'operator', 'company', 'site']);
        $isRaw = $printer && in_array($printer->connection_type, ['ESC_POS'], true);
        $payload = $isRaw
            ? ['format' => 'escpos', 'raw_base64' => base64_encode(EscPosTicketBuilder::weighbridge($ticket))]
            : ['format' => 'html', 'paper_size' => $thermalSize, 'content_base64' => base64_encode(PrintDocumentService::render('print.thermal.weighbridge', ['ticket' => $ticket, 'paperSize' => $thermalSize]))];

        $weighTimestamp = $ticket->second_weigh_at?->timestamp ?? now()->timestamp;

        $job = $this->queue('WEIGHBRIDGE_TICKET', $ticket->id, $printer, $payload, $userId, 'WEIGHBRIDGE_TICKET:'.$ticket->id.':'.$weighTimestamp, (int) Setting::get('printer.copies', 1));
        if (! $printer) {
            $job->update(['status' => 'FAILED', 'error_message' => 'Printer tiket timbangan belum dikonfigurasi.']);
        }

        return $job->refresh();
    }

    public function retry(PrintJob $job): PrintJob
    {
        $job->update(['status' => 'QUEUED', 'error_message' => null]);

        return $job->refresh();
    }
}
