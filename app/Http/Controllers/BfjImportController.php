<?php

namespace App\Http\Controllers;

use App\Models\BfjMasterAlias;
use App\Models\LegacyImportBatch;
use App\Models\LegacyImportMatch;
use App\Services\AuditService;
use App\Services\Bfj\BfjAcceptance;
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
    use Concerns\AppliesDataScope;

    public function index()
    {
        $q = LegacyImportBatch::withCount('sheets')->orderByDesc('id')->limit(30);
        $this->applyCompanyScope($q);

        return view('tools.bfj.index', ['batches' => $q->get()]);
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
        $this->ensureCompanyInScope($validated['company_id'] ?? null);
        $this->ensureSiteInScope($validated['site_id'] ?? null);
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
        $this->ensureInScope($batch);
        $batch->load(['sheets', 'issues' => fn ($q) => $q->orderByDesc('id')->limit(100), 'matches' => fn ($q) => $q->orderByDesc('id')->limit(100), 'reconciliations']);

        return view('tools.bfj.show', [
            'batch' => $batch,
            'impact' => BfjImportEngine::impact($batch),
            'report' => BfjImportEngine::report($batch),
        ]);
    }

    public function sheetAction(Request $request, LegacyImportBatch $batch)
    {
        $this->ensureInScope($batch);
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
        $this->ensureInScope($batch);
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
        $this->ensureInScope($batch);
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
            $this->audit('POSTING_FLAG', $batch->id, $validated);
        }
        $result = BfjImportEngine::import($batch->fresh());

        return redirect()->route('bfj.show', $batch)->with('success', "Import selesai: {$result['imported']} imported, {$result['skipped']} skipped.");
    }

    public function reconcile(LegacyImportBatch $batch)
    {
        $this->ensureInScope($batch);
        $batch->update(['status' => 'RECONCILING']);
        $results = BfjReconciler::reconcile($batch->fresh());
        $this->audit('RECONCILE', $batch->id, ['entries' => count($results)]);

        return redirect()->route('bfj.show', $batch)->with('success', 'Reconciliation: '.count($results).' entries.');
    }

    public function rollback(LegacyImportBatch $batch)
    {
        $this->ensureInScope($batch);
        $result = BfjImportEngine::rollback($batch);
        $this->audit('ROLLBACK', $batch->id, $result);

        return redirect()->route('bfj.show', $batch)->with('success', "Rollback: {$result['deleted']} history records removed. Posted effects need reversal.");
    }

    public function mapping(LegacyImportBatch $batch)
    {
        $this->ensureInScope($batch);

        return view('tools.bfj.mapping', [
            'batch' => $batch,
            'matches' => $batch->matches()->orderBy('entity_type')->orderByDesc('confidence')->paginate(50),
        ]);
    }

    /** Close batch: only when documented, or with an authorized exception (§89). */
    public function close(Request $request, LegacyImportBatch $batch)
    {
        $this->ensureInScope($batch);
        $validated = $request->validate([
            'close_note' => 'nullable|max:1000',
            'exception_reason' => 'nullable|max:1000',
            'exception_approver' => 'nullable|max:200',
        ]);
        $blockers = $this->closeBlockers($batch->fresh());
        if ($blockers !== [] && empty($validated['exception_reason'])) {
            return back()->with('error', 'Batch belum bisa ditutup: '.implode('; ', $blockers).'. Sertakan alasan + approver untuk pengecualian resmi.');
        }
        $batch->update([
            'status' => 'CLOSED', 'closed_at' => now(), 'closed_by' => auth()->id(),
            'close_note' => $validated['close_note'] ?? ($validated['exception_reason'] ? 'EXCEPTION by '.($validated['exception_approver'] ?? '?').': '.$validated['exception_reason'] : null),
        ]);
        $this->audit('CLOSE', $batch->id, $validated + ['blockers' => $blockers]);

        return redirect()->route('bfj.show', $batch)->with('success', 'Batch closed.');
    }

    /** Electronic migration sign-off (§90). */
    public function signoff(Request $request, LegacyImportBatch $batch)
    {
        $this->ensureInScope($batch);
        $validated = $request->validate([
            'role' => 'required|in:prepared,reviewed,finance,warehouse,hr,management',
            'name' => 'required|max:200',
        ]);
        $signoffs = $batch->signoffs ?? [];
        $signoffs[$validated['role']] = ['name' => $validated['name'], 'by' => auth()->id(), 'at' => now()->toDateTimeString()];
        $batch->update(['signoffs' => $signoffs]);
        $this->audit('SIGNOFF', $batch->id, $validated);

        return back()->with('success', 'Sign-off recorded: '.$validated['role']);
    }

    /** @return string[] blockers preventing close */
    private function closeBlockers(LegacyImportBatch $batch): array
    {
        $out = [];
        $errRows = \App\Models\LegacyImportRow::whereHas('sheet', fn ($q) => $q->where('batch_id', $batch->id)->where('action', 'IMPORT'))->where('status', 'ERROR')->count();
        if ($errRows > 0) {
            $out[] = "{$errRows} baris ERROR pada sheet IMPORT";
        }
        $unexplained = $batch->reconciliations()->where('status', 'VARIANCE')->whereNull('root_cause')->count();
        if ($unexplained > 0) {
            $out[] = "{$unexplained} variansi tanpa root cause";
        }
        $unresolved = $batch->matches()->whereIn('status', ['POSSIBLE_MATCH', 'NEW_MASTER_REQUIRED'])->count();
        if ($unresolved > 0) {
            $out[] = "{$unresolved} master belum resolved";
        }

        return $out;
    }

    /** Acceptance report card + downloads (§88). */
    public function acceptance(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('batches', ''))));
        if ($ids === []) {
            $ids = LegacyImportBatch::orderByDesc('id')->limit(20)->pluck('id')->all();
        }
        // scope filter
        $q = LegacyImportBatch::whereIn('id', $ids);
        $this->applyCompanyScope($q);
        $ids = $q->pluck('id')->all();
        $result = BfjAcceptance::run(BfjAcceptance::inferRoles($ids));

        return view('tools.bfj.acceptance', ['result' => $result, 'ids' => $ids]);
    }

    public function downloadJson(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('batches', ''))));
        $result = BfjAcceptance::run(BfjAcceptance::inferRoles($ids));

        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ->header('Content-Disposition', 'attachment; filename="bfj-acceptance.json"');
    }

    public function downloadXlsx(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('batches', ''))));
        $result = BfjAcceptance::run(BfjAcceptance::inferRoles($ids));
        $tmp = tempnam(sys_get_temp_dir(), 'bfj').'.xlsx';
        $kv = fn (array $a) => array_map(fn ($k, $v) => [$k, is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v], array_keys($a), array_values($a));
        \App\Services\Bfj\BfjExcelWriter::write($tmp, [
            ['Executive Summary', ['Metric', 'Value'], $kv(['Overall' => $result['overall_status'], 'Go-live' => $result['go_live'], 'Files' => $result['files']['recognized'].'/6'])],
            ['Readiness', ['Dimension', 'Score'], $kv($result['readiness'])],
            ['Data Quality', ['Code', 'Count'], $kv($result['data_quality'])],
        ]);

        return response()->download($tmp, 'BFJ_Acceptance_Summary.xlsx')->deleteFileAfterSend(true);
    }

    private function audit(string $action, int $id, array $data): void
    {
        try {
            AuditService::log($action, 'LEGACY_IMPORT', $id, LegacyImportBatch::class, null, $data);
        } catch (\Throwable) {
        }
    }
}
