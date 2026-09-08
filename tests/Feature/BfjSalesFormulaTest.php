<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjSalesImporter;
use Tests\TestCase;

class BfjSalesFormulaTest extends TestCase
{
    public function test_flags_formula_variance_not_silent_fix(): void
    {
        $r = BfjSalesImporter::normalize([
            'TANGGAL' => '02/09/2026', 'NO DO' => 'DO-9', 'NAMA SOPIR' => 'D', 'NO POLIS' => 'BG 8000 XX',
            'CUSTOMER' => 'C', 'JENIS MATERIAL' => 'ABU BATU', 'KUBIKASI TERJUAL' => '10',
            'HARGA' => '30000', 'RITEL' => '250000', 'REKENING ALASEN' => '0', 'REKENING PERUSAHAAN' => '0',
        ]);
        $this->assertSame('FORMULA_VARIANCE', $r['normalized']['amount_status']);
        $this->assertNotEmpty($r['issues']);
    }

    public function test_match_when_volume_times_price_equals_recorded(): void
    {
        $r = BfjSalesImporter::normalize([
            'TANGGAL' => '02/09/2026', 'NO DO' => 'DO-9', 'NAMA SOPIR' => 'D', 'NO POLIS' => 'BG 8000 XX',
            'CUSTOMER' => 'C', 'JENIS MATERIAL' => 'ABU BATU', 'KUBIKASI TERJUAL' => '10',
            'HARGA' => '30000', 'RITEL' => '300000', 'REKENING ALASEN' => '0', 'REKENING PERUSAHAAN' => '0',
        ]);
        $this->assertSame('MATCH', $r['normalized']['amount_status']);
    }
}
