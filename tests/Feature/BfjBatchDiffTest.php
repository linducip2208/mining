<?php

namespace Tests\Feature;

use App\Models\LegacyImportBatch;
use App\Services\Bfj\BfjImportEngine;
use Illuminate\Support\Facades\Storage;

class BfjBatchDiffTest extends AdminFlowTestCase
{
    public function test_modified_workbook_shows_new_unchanged_changed_removed(): void
    {
        Storage::disk('local')->put('bfj/diff-a.csv', "NOMOR SURAT,TANGGAL,PERIHAL,TUJUAN\n001/SP-BFJ/I/2026,05/01/2026,X,Y\n002/SP-BFJ/I/2026,06/01/2026,X,Y\n003/SP-BFJ/I/2026,07/01/2026,X,Y\n");
        $a = BfjImportEngine::scan('bfj/diff-a.csv', []);
        $a->sheets()->update(['detected_type' => 'CORRESPONDENCE', 'is_summary' => false, 'action' => 'IMPORT']);

        Storage::disk('local')->put('bfj/diff-b.csv', "NOMOR SURAT,TANGGAL,PERIHAL,TUJUAN\n001/SP-BFJ/I/2026,05/01/2026,X,Y\n002/SP-BFJ/I/2026,06/01/2026,CHANGED,Y\n004/SP-BFJ/I/2026,08/01/2026,X,Y\n");
        $b = BfjImportEngine::scan('bfj/diff-b.csv', []);
        $b->sheets()->update(['detected_type' => 'CORRESPONDENCE', 'is_summary' => false, 'action' => 'IMPORT']);

        $diff = BfjImportEngine::diff($a->id, $b->id);
        $this->assertCount(1, $diff['NEW']); // 004
        $this->assertCount(1, $diff['UNCHANGED']); // 001
        $this->assertCount(1, $diff['CHANGED']); // 002
        $this->assertCount(1, $diff['REMOVED_FROM_SOURCE']); // 003
    }

    public function test_rescan_identical_file_is_all_unchanged(): void
    {
        Storage::disk('local')->put('bfj/diff-c.csv', "NOMOR SURAT,TANGGAL,PERIHAL,TUJUAN\n010/SP-BFJ/I/2026,05/01/2026,X,Y\n");
        $a = BfjImportEngine::scan('bfj/diff-c.csv', []);
        $b = BfjImportEngine::scan('bfj/diff-c.csv', []);
        $diff = BfjImportEngine::diff($a->id, $b->id);
        $this->assertCount(1, $diff['UNCHANGED']);
        $this->assertCount(0, $diff['NEW']);
        $this->assertTrue($b->issues()->where('code', 'DUPLICATE_REFERENCE')->exists());
    }
}
