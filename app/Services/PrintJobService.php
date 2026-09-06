<?php

namespace App\Services;

use App\Models\PaperProfile;
use App\Models\PrinterDevice;
use App\Models\PrintJob;
use App\Models\Setting;
use Illuminate\Support\Str;

final class PrintJobService
{
    public function __construct(
        private readonly PrinterRoutingService $routing,
        private readonly PrintProfileResolver $profiles,
    ) {}

    public function queue(string $documentType, int $documentId, ?PrinterDevice $printer = null, array $payload = [], ?int $userId = null, ?string $idempotencyKey = null, int $copies = 1, ?array $profile = null, ?int $reprintOfId = null): PrintJob
    {
        $idempotencyKey ??= $documentType.':'.$documentId.':'.Str::uuid();
        $existing = PrintJob::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $paper = $profile['paper'] ?? null;
        $payload['paper_profile_code'] ??= $paper?->code;
        $payload['orientation'] ??= $profile['orientation'] ?? null;

        return PrintJob::create([
            'uuid' => (string) Str::uuid(),
            'idempotency_key' => $idempotencyKey,
            'document_type' => strtoupper($documentType),
            'document_id' => $documentId,
            'printer_device_id' => $printer?->id,
            'paper_profile_id' => $paper?->id,
            'workstation' => $printer?->workstation,
            'copies' => max(1, min(3, $copies)),
            'status' => 'QUEUED',
            'payload' => $payload,
            'paper_snapshot' => $paper ? [
                'id' => $paper->id,
                'code' => $paper->code,
                'name' => $paper->name,
                'paper_type' => $paper->paper_type,
                'width_mm' => $paper->width_mm,
                'height_mm' => $paper->height_mm,
                'orientation' => $profile['orientation'] ?? $paper->orientation,
                'margins' => [$paper->margin_top_mm, $paper->margin_right_mm, $paper->margin_bottom_mm, $paper->margin_left_mm],
            ] : null,
            'printer_snapshot' => $printer ? [
                'id' => $printer->id,
                'name' => $printer->name,
                'printer_type' => $printer->printer_type,
                'connection_type' => $printer->connection_type,
                'character_width' => $printer->characterWidth($paper),
            ] : null,
            'orientation_snapshot' => $profile['orientation'] ?? $paper?->orientation,
            'paper_width_mm' => $paper?->width_mm,
            'paper_height_mm' => $paper?->height_mm,
            'reprint_of_id' => $reprintOfId,
            'requested_by' => $userId,
            'requested_at' => now(),
        ]);
    }

    public function queueWeighbridge($ticket, ?int $userId = null): PrintJob
    {
        $profile = $this->profiles->resolve('WEIGHBRIDGE_TICKET', $ticket->company_id, $ticket->site_id, null, null, $userId);
        $printer = $profile['printer'] ?? $this->routing->resolve('WEIGHBRIDGE_TICKET', $ticket->company_id, $ticket->site_id);
        $paper = $profile['paper'] ?? null;
        $thermalSize = $paper?->width_mm ? ((int) $paper->width_mm).'mm' : ($printer?->paper_size ?: Setting::get('printer.thermal_width', '80mm'));
        $ticket->load(['weighbridge', 'customer', 'supplier', 'item', 'operator', 'company', 'site']);
        $isRaw = $printer && in_array($printer->connection_type, ['ESC_POS', 'BLUETOOTH', 'USB', 'NETWORK'], true) && $paper?->isThermal();
        $payload = $isRaw
            ? ['format' => 'escpos', 'raw_base64' => base64_encode(EscPosTicketBuilder::weighbridge($ticket, $paper, $printer)), 'auto_cut' => (bool) ($profile['auto_cut'] ?? false)]
            : ['format' => 'html', 'paper_size' => $thermalSize, 'content_base64' => base64_encode(PrintDocumentService::render($paper?->code === 'THERMAL_58' ? 'print.thermal.58mm' : 'print.thermal.80mm', ['ticket' => $ticket, 'paperSize' => $thermalSize]))];

        $weighTimestamp = $ticket->second_weigh_at?->timestamp ?? now()->timestamp;
        $job = $this->queue('WEIGHBRIDGE_TICKET', $ticket->id, $printer, $payload, $userId, 'WEIGHBRIDGE_TICKET:'.$ticket->id.':'.$weighTimestamp, (int) ($profile['copies'] ?? Setting::get('printer.copies', 1)), $profile);
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