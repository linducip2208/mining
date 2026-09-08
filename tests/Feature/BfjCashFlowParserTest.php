<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjFinanceImporter;
use Tests\TestCase;

class BfjCashFlowParserTest extends TestCase
{
    public function test_classifies_flows_and_internal_transfer(): void
    {
        $t = BfjFinanceImporter::normalize(['TANGGAL' => '03/08/2026', 'KETERANGAN' => 'Transfer internal ke kas kecil', 'MASUK' => '0', 'KELUAR' => '500000']);
        $this->assertSame('TRANSFER_INTERNAL', $t['normalized']['flow_type']);
        $o = BfjFinanceImporter::normalize(['TANGGAL' => '01/08/2026', 'KETERANGAN' => 'SALDO AWAL KAS', 'MASUK' => '5000000', 'KELUAR' => '0']);
        $this->assertSame('OPENING_BALANCE', $o['normalized']['flow_type']);
    }
}
