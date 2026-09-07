<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\LetterRegister;
use App\Services\ImportService;
use Illuminate\Support\Facades\Storage;

class LegacyLetterImportTest extends AdminFlowTestCase
{
    public function test_letter_import_without_journal_or_number_clash(): void
    {
        $path = 'imports/test-'.uniqid().'.csv';
        Storage::disk('local')->put($path, "NOMOR SURAT,TANGGAL,JENIS SURAT,PERIHAL,TUJUAN\n001/SP-BFJ/I/2024,2024-01-05,Surat Penawaran,Penawaran unit,PT Maju\n");
        $batch = ImportBatch::create(['type' => 'letter_register', 'file_name' => $path, 'status' => 'UPLOADED']);
        $map = ['NOMOR SURAT' => 'number', 'TANGGAL' => 'date', 'JENIS SURAT' => 'type', 'PERIHAL' => 'subject', 'TUJUAN' => 'recipient'];

        $result = ImportService::validate($batch, $map);
        $this->assertEquals(1, $result['valid']);
        $batch->update(['column_map' => $map, 'status' => 'VALIDATED']);
        [$imported] = ImportService::execute($batch);
        $this->assertEquals(1, $imported);

        $letter = LetterRegister::where('number', '001/SP-BFJ/I/2024')->firstOrFail();
        $this->assertEquals('LEGACY_IMPORT', $letter->source);
    }
}
