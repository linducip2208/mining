<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjSparepartImporter;
use Tests\TestCase;

class BfjSparepartMasterImportTest extends TestCase
{
    public function test_master_fields_mapped(): void
    {
        $r = BfjSparepartImporter::normalizeMaster(['KODE' => 'SPR-001', 'NAMA SPAREPART' => 'Filter', 'KATEGORI' => 'Filter', 'SATUAN' => 'PCS', 'LOKASI' => 'A01-01']);
        $this->assertSame('SPR-001', $r['normalized']['code']);
        $this->assertEmpty($r['issues']);
    }
}
