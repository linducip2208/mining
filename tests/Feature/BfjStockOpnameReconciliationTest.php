<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjClassifier;
use Tests\TestCase;

class BfjStockOpnameReconciliationTest extends TestCase
{
    public function test_opname_detected_with_no_stock_effect_by_default(): void
    {
        $r = BfjClassifier::classifySheet('OPNAME AGUSTUS', ['KODE', 'STOK SISTEM', 'STOK FISIK', 'SELISIH', 'KETERANGAN']);
        $this->assertSame('STOCK_OPNAME', $r['type']);
    }
}
