<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjImportEngine;
use Illuminate\Support\Facades\Storage;

class BfjLetterLegacyNumberTest extends AdminFlowTestCase
{
    public function test_legacy_number_preserved_sequence_not_advanced(): void
    {
        Storage::disk('local')->put('bfj/t-lett.csv', "NOMOR SURAT,TANGGAL,PERIHAL,TUJUAN\n001/SP-BFJ/I/2026,05/01/2026,X,Y\n");
        $batch = BfjImportEngine::scan('bfj/t-lett.csv', []);
        $sheet = $batch->sheets()->first();
        $sheet->update(['detected_type' => 'CORRESPONDENCE', 'is_summary' => false, 'action' => 'IMPORT']);
        BfjImportEngine::import($batch->fresh());
        $this->assertDatabaseHas('letter_registers', ['number' => '001/SP-BFJ/I/2026']);
    }
}
