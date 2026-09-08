<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjNormalizer;
use App\Services\Bfj\BfjSalesImporter;
use Tests\TestCase;

class BfjSalesPaymentChannelTest extends TestCase
{
    public function test_channels_map_to_cash_clearing_bank(): void
    {
        $this->assertSame('PERSONAL_CLEARING', BfjNormalizer::paymentChannel('REKENING ALASEN'));
        $this->assertSame('COMPANY_BANK', BfjNormalizer::paymentChannel('REKENING PERUSAHAAN'));
        $r = BfjSalesImporter::normalize([
            'TANGGAL' => '02/09/2026', 'NO DO' => 'DO-1', 'NAMA SOPIR' => 'D', 'NO POLIS' => 'BG 8000 XX',
            'CUSTOMER' => 'C', 'JENIS MATERIAL' => 'ABU BATU', 'KUBIKASI TERJUAL' => '5', 'HARGA' => '30000',
            'RITEL' => '0', 'REKENING ALASEN' => '150000', 'REKENING PERUSAHAAN' => '0',
        ]);
        $this->assertSame('PERSONAL_CLEARING', $r['normalized']['payment_channel']);
    }
}
