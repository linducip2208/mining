<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Services\AuditService;
use App\Services\LocalPrintAgentService;
use Illuminate\Http\Request;

class PrintJobController extends Controller
{
    public function retry(PrintJob $print_job)
    {
        abort_unless(auth()->user()->hasPermission('printer.manage'), 403);
        $print_job->update(['status' => 'QUEUED', 'error_message' => null]);
        AuditService::log('PRINT_RETRY', 'PRINTER', $print_job->id, PrintJob::class, null, ['job_uuid' => $print_job->uuid]);

        return back()->with('success', 'Print job dimasukkan kembali ke antrean.');
    }

    public function package(PrintJob $print_job)
    {
        abort_unless(auth()->user()->hasPermission('printer.view') || auth()->user()->hasPermission('weighbridge.print'), 403);

        return response()->json(app(LocalPrintAgentService::class)->package($print_job));
    }

    public function clientStatus(Request $request, PrintJob $print_job)
    {
        abort_unless(auth()->user()->hasPermission('printer.view') || auth()->user()->hasPermission('weighbridge.print'), 403);
        $data = $request->validate(['status' => 'required|in:PRINTED,FAILED', 'error_message' => 'nullable|string|max:1000']);
        $print_job->update(['status' => $data['status'], 'printed_at' => $data['status'] === 'PRINTED' ? now() : null, 'error_message' => $data['error_message'] ?? null]);

        return response()->json(['ok' => true]);
    }

    public function agentStatus(Request $request, PrintJob $print_job)
    {
        abort_unless(app(LocalPrintAgentService::class)->validSignature(
            $request->getContent(),
            $request->method(),
            $request->header('X-Mining-Agent-Timestamp'),
            $request->header('X-Mining-Agent-Signature')
        ), 401);
        $data = $request->validate(['status' => 'required|in:SENDING,PRINTED,FAILED,CANCELLED', 'error_message' => 'nullable|string|max:1000']);
        $print_job->update([
            'status' => $data['status'],
            'printed_at' => $data['status'] === 'PRINTED' ? now() : null,
            'error_message' => $data['error_message'] ?? null,
        ]);
        if ($print_job->printer) {
            $print_job->printer->update(['status' => $data['status'] === 'PRINTED' ? 'ONLINE' : 'OFFLINE', 'last_seen_at' => now(), 'last_error' => $data['error_message'] ?? null]);
        }

        return response()->json(['ok' => true]);
    }
}
