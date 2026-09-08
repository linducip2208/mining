<?php

namespace App\Http\Controllers;

use App\Models\BfjMasterAlias;
use App\Models\LegacyImportBatch;
use App\Models\LegacyImportMatch;
use App\Services\AuditService;
use App\Services\Bfj\BfjImportEngine;
use App\Services\Bfj\BfjReconciler;
use App\Support\SpreadsheetReader;
use Illuminate\Http\Request;

/**
 * TOOLS → DATA MIGRATION → BFJ LEGACY IMPORT (§2, §107-§108).
 * Enterprise dashboard over the staging engine; never writes ERP data
 * before explicit IMPORT confirmation with impact simulation.
 */
class BfjImportController extends Controller
{
    public function index()
    {
        return view('tools.bfj.index', [
            'batches' => LegacyImportBatch::withCount('sheets')->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200',
            'company_id' => 'nullable|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'mode' => 'nullable|max:40',
            'cutoff_date' => 'nullable|date',
        ]);
        $file = $request->file('file');
        SpreadsheetReader::assertSafe($file->getClientOriginalName(), (string) $file->getMimeType());
        $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
        abort_unless(in_array($ext, SpreadsheetReader::SUPPORTED, true), 422, 'Format tidak didukung. Gunakan .xlsx, .xls, atau .csv.');
        $path = $file->store('bfj-imports', 'local');
        $batch = BfjImportEngine::scan($path, [
            'company_id' => $validated['company_id'] ?? null,
            'site_id' => $validated['site_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'mode' => $validated['mode'] ?? 'HISTORY_ONLY',
            'cutoff_date' => $validated['cutoff_date'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('bfj.show', $batch)->with('success', 'Workbook terdeteksi: '.$batch->sheets->count().' sheets.');
    }

    public function show(LegacyImportBatch $batch)
    {
        $batch->load(['sheets', 'issues' => fn ($q) => $q->orderByDesc('id')->limit(100), 'matches' => fn ($q) => $q->orderByDesc('id')->limit(100), 'reconciliations']);

        return view('tools.bfj.show', [
            'batch' => $batch,
            'impact' => BfjImportEngine::impact($batch),
            'report' => BfjImportEngine::report($batch),
        ]);
    }

    public function sheetAction(Request $request, LegacyImportBatch $batch)
    {
        $validated = $request->validate([
            'sheet_id' => 'required|exists:legacy_import_sheets,id',
            'action' => 'required|in:IMPORT,RECONCILE_ONLY,IGNORE',
        ]);
        $batch->sheets()->where('id', $validated['sheet_id'])->update(['action' => $validated['action']]);
        try {
            AuditService::log('UPDATE', 'LEGACY_IMPORT', $batch->id, LegacyImportBatch::class, null, $validated);
        } catch (\Throwable) {
        }

        return back()->with('success', 'Sheet action updated.');
    }

    public function resolveMaster(Request $request, LegacyImportBatch $batch)
    {
        $validated = $request->validate([
            'match_id' => 'required|exists:legacy_import_matches,id',
            'target_type' => 'nullable|max:100',
            'target_id' => 'nullable|integer',
            'decision' => 'required|in:match,create,ignore',
        ]);
        $match = LegacyImportMatch::where('batch_id', $batch->id)->findOrFail($validated['match_id']);
        if ($validated['decision'] === 'ignore') {
            $match->update(['status' => 'IGNORED']);
        } elseif ($validated['decision'] === 'match' && $validated['target_type'] && $validated['target_id']) {
            $match->update(['status' => 'RESOLVED', 'target_type' => $validated['target_type'], 'target_id' => $validated['target_id']]);
            BfjMasterAlias::updateOrCreate(
                ['entity_type' => $match->entity_type, 'normalized_value' => $match->normalized_value ?? $match->legacy_value],
                ['legacy_value' => $match->legacy_value, 'target_type' => $validated['target_type'], 'target_id' => $validated['target_id'], 'status' => 'RESOLVED', 'confidence' => 100]
            );
        } else {
            $match->update(['status' => 'NEW_MASTER_REQUIRED']);
        }
        try {
            AuditService::log('UPDATE', 'LEGACY_MASTER_MAP', $match->id, LegacyImportMatch::class, null, $validated);
        } catch (\Throwable) {
        }

        return back()->with('success', 'Master mapping saved for future imports.');
    }

    public function import(Request $request, LegacyImportBatch $batch)
    {
        $validated = $request->validate([
            'allow_accounting_posting' => 'nullable|boolean',
            'allow_stock_posting' => 'nullable|boolean',
            'confirm_impact' => 'required|accepted',
        ]);
        if (! empty($validated['allow_accounting_posting']) || ! empty($validated['allow_stock_posting'])) {
            abort_unless(auth()->user()?->can('finance.post') || auth()->user()?->username === 'superadmin', 403, 'Posting legacy requires Finance authorization.');
            $batch->update([
                'allow_accounting_posting' => (bool) ($validated['allow_accounting_posting'] ?? false),
                'allow_stock_posting' => (bool) ($validated['allow_stock_posting'] ?? false),
            ]);
        }
        $result = BfjImportEngine::import($batch->fresh());

        return redirect()->route('bfj.show', $batch)->with('success', "Import selesai: {$result['imported']} imported, {$result['skipped']} skipped.");
    }

    public function reconcile(LegacyImportBatch $batch)
    {
        $batch->update(['status' => 'RECONCILING']);
        $results = BfjReconciler::reconcile($batch->fresh());

        return redirect()->route('bfj.show', $batch)->with('success', 'Reconciliation: '.count($results).' entries.');
    }

    public function rollback(LegacyImportBatch $batch)
    {
        $result = BfjImportEngine::rollback($batch);

        return redirect()->route('bfj.show', $batch)->with('success', "Rollback: {$result['deleted']} history records removed. Posted effects need reversal.");
    }

    public function mapping(LegacyImportBatch $batch)
    {
        return view('tools.bfj.mapping', [
            'batch' => $batch,
            'matches' => $batch->matches()->orderBy('entity_type')->orderByDesc('confidence')->paginate(50),
        ]);
    }
}
