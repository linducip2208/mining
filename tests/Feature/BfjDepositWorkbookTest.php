<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjClassifier;
use Tests\TestCase;

class BfjDepositWorkbookTest extends TestCase
{
    public function test_detects_deposit_workbook(): void
    {
        $r = BfjClassifier::classifyFile('DEPOSIT MATERIAL BULAN AGUSTUS 2026.xlsx', ['SISA DEPOSIT', 'RITEL']);
        $this->assertSame('CUSTOMER_DEPOSIT', $r['type']);
        $sisa = BfjClassifier::classifySheet('SISA DEPOSIT', ['CUSTOMER', 'DEPOSIT']);
        $this->assertTrue($sisa['is_summary']);
    }
}
