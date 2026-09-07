<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Item;
use App\Services\ImportService;
use Illuminate\Support\Facades\Storage;

class LegacyImportDuplicateTest extends AdminFlowTestCase
{
    public function test_rerun_same_file_skips_duplicates(): void
    {
        $path = 'imports/test-'.uniqid().'.csv';
        Storage::disk('local')->put($path, "KODE,NAMA SPAREPART\nSPR-200,Bearing X\n");
        $batch = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => $path, 'status' => 'VALIDATED', 'column_map' => ['KODE' => 'code', 'NAMA SPAREPART' => 'name']]);

        [$first] = ImportService::execute($batch);
        $this->assertEquals(1, $first);

        $batch2 = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => $path, 'status' => 'VALIDATED', 'column_map' => ['KODE' => 'code', 'NAMA SPAREPART' => 'name']]);
        [$second, $skipped] = ImportService::execute($batch2);
        $this->assertEquals(0, $second);
        $this->assertEquals(1, $skipped);
        $this->assertEquals(1, Item::where('code', 'SPR-200')->count());
    }
}
