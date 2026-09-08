<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjFingerprinter;
use Tests\TestCase;

class BfjSalesDuplicateTest extends TestCase
{
    public function test_same_sale_same_fingerprint(): void
    {
        $a = ['transaction_date' => '2026-09-02', 'legacy_do_number' => 'DO-1', 'customer' => 'C', 'vehicle' => 'BG 8000 XX', 'material' => 'ABU BATU', 'volume_m3' => 10, 'gross_amount' => 300000];
        $this->assertSame(BfjFingerprinter::sales($a), BfjFingerprinter::sales($a));
        $b = array_merge($a, ['volume_m3' => 11]);
        $this->assertNotSame(BfjFingerprinter::sales($a), BfjFingerprinter::sales($b));
    }
}
