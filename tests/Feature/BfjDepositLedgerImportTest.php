<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjDepositImporter;
use Tests\TestCase;

class BfjDepositLedgerImportTest extends TestCase
{
    public function test_consumption_vs_deposit_typed(): void
    {
        $dep = BfjDepositImporter::normalize(['TANGGAL' => '01/08/2026', 'DEPOSIT' => '1500000', 'KET' => 'SALDO AWAL']);
        $this->assertSame('DEPOSIT', $dep['normalized']['type']);
        $use = BfjDepositImporter::normalize(['TANGGAL' => '05/08/2026', 'NO DO' => 'DO-1', 'PENJUALAN' => '300000', 'KET' => 'PENGIRIMAN']);
        $this->assertSame('CONSUMPTION', $use['normalized']['type']);
        $this->assertEquals(300000, $use['normalized']['amount']);
    }
}
