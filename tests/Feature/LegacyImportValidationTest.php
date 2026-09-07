<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Services\ImportService;
use Illuminate\Support\Facades\Storage;

class LegacyImportValidationTest extends AdminFlowTestCase
{
    public function test_invalid_rows_reported_with_row_numbers(): void
    {
        $path = 'imports/test-'.uniqid().'.csv';
        Storage::disk('local')->put($path, "KODE,NAMA SPAREPART,MINIMUM STOCK\n, Tanpa Kode,abc\nSPR-201,Valid,10\n");
        $batch = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => $path, 'status' => 'UPLOADED']);
        $map = ['KODE' => 'code', 'NAMA SPAREPART' => 'name', 'MINIMUM STOCK' => 'min_stock'];

        $result = ImportService::validate($batch, $map);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['valid']);
        $this->assertEquals(1, $result['failed']);
        $this->assertNotEmpty($result['errors']);
        $this->assertEquals(2, $result['errors'][0]['row']);
    }
}
