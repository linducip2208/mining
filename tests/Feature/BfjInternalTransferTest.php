<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjFinanceImporter;
use Tests\TestCase;

class BfjInternalTransferTest extends TestCase
{
    public function test_transfer_has_no_revenue_effect(): void
    {
        $r = BfjFinanceImporter::normalize(['TANGGAL' => '03/08/2026', 'KETERANGAN' => 'Transfer internal antar kas', 'MASUK' => '500000', 'KELUAR' => '500000']);
        $this->assertSame('TRANSFER_INTERNAL', $r['normalized']['flow_type']);
        $this->assertEquals(0, $r['normalized']['net']);
    }
}
