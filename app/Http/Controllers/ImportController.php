<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\AuditService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Tools → Import Data wizard: UPLOAD → MAP → VALIDATE → PREVIEW → IMPORT.
 * Nothing is written before explicit execute.
 */
class ImportController extends Controller
{
    public function index()
    {
        return view('tools.import-index', [
            'types' => ImportService::types(),
            'batches' => ImportBatch::orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:'.implode(',', array_keys(ImportService::types())),
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);
        $path = $request->file('file')->store('imports', 'local');
        $batch = ImportBatch::create([
            'type' => $validated['type'],
            'file_name' => $path,
            'status' => 'UPLOADED',
        ]);
        AuditService::log('CREATE', 'IMPORT', $batch->id, ImportBatch::class, null, ['type' => $batch->type]);

        return redirect()->route('imports.map', $batch);
    }

    public function map(ImportBatch $batch)
    {
        $data = ImportService::readCsv($batch);
        $def = ImportService::types()[$batch->type];

        return view('tools.import-map', compact('batch', 'data', 'def'));
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

    public function execute(ImportBatch $batch)
    {
        if (! in_array($batch->status, ['VALIDATED', 'MAPPED'], true)) {
            return back()->with('error', 'Batch harus divalidasi dulu.');
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
            fputcsv($out, ['ROW', 'FIELD', 'VALUE', 'ERROR']);
            foreach ($result['errors'] as $e) {
                fputcsv($out, [$e['row'] ?? '', '', '', $e['error'] ?? '']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(ImportBatch $batch)
    {
        if ($batch->status === 'IMPORTED') {
            return back()->with('error', 'Batch yang sudah diimport tidak dapat dihapus (jejak audit).');
        }
        Storage::delete($batch->file_name);
        $batch->delete();

        return back()->with('success', 'Batch dihapus.');
    }
}
