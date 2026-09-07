<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\NumberingService;

class ReceiptNumberingTest extends AdminFlowTestCase
{
    public function test_receipt_format_configurable_and_unique(): void
    {
        Setting::set('numbering.receipt_format', 'KW/{SEQ:4}/{MONTH_ROMAN}/{YEAR}');
        $a = NumberingService::generate('KWITANSI', $this->co->id);
        $b = NumberingService::generate('KWITANSI', $this->co->id);

        $this->assertNotEquals($a, $b);
        $this->assertMatchesRegularExpression('#^KW/\d{4}/[IVX]+/\d{4}$#', $a);
    }
}
