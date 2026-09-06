<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\WeighbridgeTicket;
use App\Services\BrandingService;
use App\Services\DocumentVerificationService;
use App\Support\HumanLabel;
use Illuminate\Http\Request;

final class DocumentVerificationController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $payload = DocumentVerificationService::decode($token);
        abort_unless($payload && filled($payload['reference'] ?? null), 404);
        $record = match ($payload['type'] ?? null) {
            'invoice' => Invoice::with('company')->where('number', $payload['reference'])->first(),
            'weighbridge' => WeighbridgeTicket::with('company')->where('ticket_no', $payload['reference'])->first(),
            default => null,
        };
        abort_unless($record, 404);

        return view('documents.verify', [
            'payload' => $payload,
            'label' => HumanLabel::label($payload['type'] ?? 'Dokumen'),
            'companyName' => $record->company?->name ?: BrandingService::companyName(),
            'record' => $record,
        ]);
    }
}
