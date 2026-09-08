<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjNormalizer;
use Tests\TestCase;

class BfjClearingAccountTest extends TestCase
{
    public function test_personal_channel_is_clearing_not_bank(): void
    {
        $this->assertSame('PERSONAL_CLEARING', BfjNormalizer::paymentChannel('Penjualan Transfer ke Rekening personal'));
        $this->assertSame('COMPANY_BANK', BfjNormalizer::paymentChannel('Penjualan Transfer ke Rekening Perusahaan'));
    }
}
