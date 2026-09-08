<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjImportEngine;
use App\Services\Bfj\BfjParsers;
use Illuminate\Support\Facades\Storage;

class BfjEngineIdempotencyTest extends AdminFlowTestCase
{
    public function test_rescan_same_file_warns_and_reimport_no_duplicates(): void
    {
        Storage::disk('local')->put('bfj/dup.csv', "NOMOR SURAT,TANGGAL,PERIHAL,TUJUAN\n090/SP-BFJ/I/2026,05/01/2026,X,Y\n");
        $b1 = BfjImportEngine::scan('bfj/dup.csv', []);
        $b1->sheets()->update(['detected_type' => 'CORRESPONDENCE', 'is_summary' => false, 'action' => 'IMPORT']);
        BfjImportEngine::import($b1->fresh());
        $b2 = BfjImportEngine::scan('bfj/dup.csv', []);
        $this->assertTrue($b2->issues()->where('code', 'DUPLICATE_REFERENCE')->exists());
        $this->assertDatabaseCount('letter_registers', 1);
    }

    public function test_indonesian_number_parsing(): void
    {
        $this->assertEquals(135975000, BfjParsers::parseMoney('Rp135.975.000')['value']);
        $this->assertEquals(388500000, BfjParsers::parseMoney('Rp388,500,000')['value']);
        $this->assertEquals(4.51, BfjParsers::parseQty('4,51')['value']);
        $this->assertEquals(13.91, BfjParsers::parseQty('13.91')['value']);
    }

    public function test_dry_run_writes_nothing(): void
    {
        Storage::disk('local')->put('bfj/dry.csv', "NOMOR SURAT,TANGGAL,PERIHAL,TUJUAN\n091/SP-BFJ/I/2026,05/01/2026,X,Y\n");
        $b = BfjImportEngine::scan('bfj/dry.csv', []);
        $impact = BfjImportEngine::impact($b);
        $this->assertArrayHasKey('records_to_create', $impact);
        $this->assertDatabaseMissing('letter_registers', ['number' => '091/SP-BFJ/I/2026']);
    }
}
