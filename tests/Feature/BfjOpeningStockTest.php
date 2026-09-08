<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjSparepartImporter;
use Tests\TestCase;

class BfjOpeningStockTest extends TestCase
{
    public function test_stok_lama_is_opening_not_receipt(): void
    {
        $r = BfjSparepartImporter::normalizeMovement(['TANGGAL' => '01/08/2026', 'KODE SPAREPART' => 'SPR-001', 'QTY MASUK' => '20', 'KETERANGAN' => 'STOK LAMA'], 'RECEIPT');
        $this->assertSame('OPENING_BALANCE', $r['normalized']['movement']);
        $this->assertTrue($r['normalized']['is_opening']);
    }
}
