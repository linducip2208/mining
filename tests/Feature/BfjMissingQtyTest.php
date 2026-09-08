<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjSparepartImporter;
use Tests\TestCase;

class BfjMissingQtyTest extends TestCase
{
    public function test_blank_qty_is_error_not_zero(): void
    {
        $r = BfjSparepartImporter::normalizeMovement(['TANGGAL' => '01/08/2026', 'KODE' => 'SPR-001', 'QTY MASUK' => ''], 'RECEIPT');
        $this->assertNull($r['normalized']['qty']);
        $this->assertSame('ERROR_MISSING_QTY', $r['issues'][0]['code']);
    }
}
