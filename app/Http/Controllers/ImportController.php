<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\AuditService;
use App\Services\ImportService;
use App\Support\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Tools → Import Data wizard:
 * UPLOAD → SHEET → MAP (auto + manual) → VALIDATE → PREVIEW → IMPORT.
 * Native .xlsx / .xls / .csv (macro-enabled refused). Nothing is written
 * before explicit execute.
 */
class ImportController extends Controller
{
    public function index()
    {
        return view('tools.import-index', [
            'types' => ImportService::types(),
            'modes' => ImportService::MODES,
            'batches' => ImportBatch::orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:'.implode(',', array_keys(ImportService::types())),
            'file' => 'required|file|max:20480',
            'company_id' => 'nullable|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sheet' => 'nullable|max:100',
            'mode' => 'nullable|max:40',
        ]);
        $file = $request->file('file');
        SpreadsheetReader::assertSafe($file->getClientOriginalName(), (string) $file->getMimeType());
        $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
        if (! in_array($ext, SpreadsheetReader::SUPPORTED, true)) {
            return back()->with('error', 'Format .'.$ext.' tidak didukung. Gunakan .xlsx, .xls, atau .csv.');
        }
        $path = $file->store('imports', 'local');
        $batch = ImportBatch::create([
            'type' => $validated['type'],
            'file_name' => $path,
            'file_hash' => hash_file('sha256', Storage::path($path)) ?: '',
            'file_ext' => $ext,
            'sheet' => $validated['sheet'] ?? null,
            'mode' => $validated['mode'] ?? (ImportService::DEFAULT_MODES[$validated['type']] ?? null),
            'company_id' => $validated['company_id'] ?? null,
            'site_id' => $validated['site_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'status' => 'UPLOADED',
        ]);
        // workbook with multiple sheets: send user to sheet picker
        $sheets = SpreadsheetReader::sheets(Storage::path($path));
        AuditService::log('CREATE', 'IMPORT', $batch->id, ImportBatch::class, null, ['type' => $batch->type, 'ext' => $ext]);
        if (count($sheets) > 1 && ! $batch->sheet) {
            return redirect()->route('imports.sheet', $batch);
        }

        return redirect()->route('imports.map', $batch);
    }

    public function sheet(ImportBatch $batch)
    {
        $sheets = SpreadsheetReader::sheets(Storage::path($batch->file_name));

        return view('tools.import-sheet', ['batch' => $batch, 'sheets' => $sheets]);
    }

    public function selectSheet(Request $request, ImportBatch $batch)
    {
        $validated = $request->validate(['sheet' => 'required|max:100']);
        $sheets = SpreadsheetReader::sheets(Storage::path($batch->file_name));
        abort_unless(in_array($validated['sheet'], $sheets, true), 422, 'Sheet tidak ditemukan.');
        $batch->update(['sheet' => $validated['sheet']]);

        return redirect()->route('imports.map', $batch);
    }

    public function map(ImportBatch $batch)
    {
        $data = ImportService::readWorkbook($batch);
        $def = ImportService::types()[$batch->type];
        $autoMap = ImportService::autoMap($data['headers'], $batch->type);

        return view('tools.import-map', ['batch' => $batch, 'data' => $data, 'def' => $def, 'autoMap' => $autoMap]);
    }

    public function validateMap(Request $request, ImportBatch $batch)
    {
        $validated = $request->validate(['map' => 'required|array']);
        $result = ImportService::validate($batch, $validated['map']);
        $batch->update([
            'column_map' => $validated['map'],
            'preview' => array_slice($result['rows'], 0, 20),
            'total_rows' => $result['total'],
            'valid_rows' => $result['valid'],
            'warning_rows' => $result['warnings'],
            'failed_rows' => $result['failed'],
            'status' => 'VALIDATED',
        ]);

        return view('tools.import-preview', ['batch' => $batch->fresh(), 'result' => $result]);
    }

    public function execute(Request $request, ImportBatch $batch)
    {
        if (! in_array($batch->status, ['VALIDATED', 'MAPPED'], true)) {
            return back()->with('error', 'Batch harus divalidasi dulu.');
        }
        $force = $request->boolean('force');
        if (ImportService::fileSeenBefore($batch->file_hash) && ! $force) {
            return back()->with('error', 'File dengan konten identik pernah diimport. Centang "Import ulang paksa" jika memang sengaja.');
        }
        if ($force) {
            $batch->update(['force_import' => true]);
            AuditService::log('IMPORT_FORCE', 'IMPORT', $batch->id, ImportBatch::class, null, ['type' => $batch->type]);
        }
        [$imported, $skipped] = ImportService::execute($batch);

        return redirect()->route('imports.index')->with('success', "Import selesai: {$imported} masuk, {$skipped} duplikat dilewati.");
    }

    public function errors(ImportBatch $batch)
    {
        $result = ImportService::validate($batch, $batch->column_map ?? []);
        $filename = 'import-errors-'.$batch->id.'.csv';

        return response()->streamDownload(function () use ($result) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ROW', 'COLUMN', 'VALUE', 'ERROR', 'SUGGESTED ACTION']);
            foreach ($result['errors'] as $e) {
                fputcsv($out, [$e['row'] ?? '', $e['column'] ?? '', $e['value'] ?? '', $e['error'] ?? '', $e['action'] ?? '']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(ImportBatch $batch)
    {
        if ($batch->status === 'IMPORTED') {
            return back()->with('error', 'Batch yang sudah diimport tidak dapat dihapus (jejak audit). Rollback via reversal bila diperlukan.');
        }
        Storage::delete($batch->file_name);
        $batch->delete();

        return back()->with('success', 'Batch dihapus.');
    }
}
