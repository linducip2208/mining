<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Item;
use App\Services\ImportService;
use Illuminate\Support\Facades\Storage;

class LegacySparepartImportTest extends AdminFlowTestCase
{
    protected function makeBatch(string $csv): ImportBatch
    {
        $path = 'imports/test-'.uniqid().'.csv';
        Storage::disk('local')->put($path, $csv);

        return ImportBatch::create(['type' => 'sparepart_master', 'file_name' => $path, 'status' => 'UPLOADED']);
    }

    public function test_master_import_creates_flagged_items(): void
    {
        $batch = $this->makeBatch("KODE,NAMA SPAREPART,KATEGORI,SATUAN,MINIMUM STOCK\nSPR-100,Filter Udara,Filter,PCS,5\n");
        $map = ['KODE' => 'code', 'NAMA SPAREPART' => 'name', 'KATEGORI' => 'category', 'SATUAN' => 'unit', 'MINIMUM STOCK' => 'min_stock'];

        $result = ImportService::validate($batch, $map);
        $this->assertEquals(1, $result['valid']);
        $this->assertEquals(0, $result['failed']);
        $batch->update(['column_map' => $map, 'status' => 'VALIDATED']);

        [$imported, $skipped] = ImportService::execute($batch);
        $this->assertEquals(1, $imported);
        $this->assertEquals(0, $skipped);

        $item = Item::where('code', 'SPR-100')->firstOrFail();
        $this->assertEquals('LEGACY_IMPORT', $item->source);
        $this->assertEquals('SPAREPART', $item->type);
    }
}
