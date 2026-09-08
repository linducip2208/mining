<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjDepositImporter;
use Tests\TestCase;

class BfjMaterialDepositTest extends TestCase
{
    public function test_material_entitlement_fields_preserved(): void
    {
        $r = BfjDepositImporter::normalize(['TANGGAL' => '05/08/2026', 'MATERIAL' => 'ABU BATU', 'VOLUME' => '10', 'PENJUALAN' => '300000', 'NO DO' => 'DO-1']);
        $this->assertSame('ABU BATU', $r['normalized']['material']);
        $this->assertEquals(10, $r['normalized']['volume']);
    }
}
