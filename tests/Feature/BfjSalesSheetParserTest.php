<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjSalesImporter;
use Tests\TestCase;

class BfjSalesSheetParserTest extends TestCase
{
    public function test_normalizes_daily_row(): void
    {
        $r = BfjSalesImporter::normalize([
            'TANGGAL' => '02/09/2026', 'NO DO' => 'DO-0001', 'NAMA SOPIR' => 'Driver A',
            'NO POLIS' => 'BG8506DS', 'CUSTOMER' => 'Customer A', 'JENIS MATERIAL' => 'ABU BATU',
            'KUBIKASI TERJUAL' => '10', 'HARGA' => '30000', 'RITEL' => '300000',
            'REKENING ALASEN' => '0', 'REKENING PERUSAHAAN' => '0', 'KET' => 'RITEL',
        ]);
        $n = $r['normalized'];
        $this->assertSame('2026-09-02', $n['transaction_date']);
        $this->assertSame('BG 8506 DS', $n['vehicle']);
        $this->assertSame('CASH', $n['payment_channel']);
        $this->assertEquals(300000, $n['gross_amount']);
    }
}
