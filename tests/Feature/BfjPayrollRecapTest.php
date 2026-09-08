<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjPayrollImporter;
use Tests\TestCase;

class BfjPayrollRecapTest extends TestCase
{
    public function test_rekap_is_control_total_not_source(): void
    {
        $this->assertTrue(BfjPayrollImporter::isSummarySheet('REKAP'));
        $this->assertFalse(BfjPayrollImporter::isSummarySheet('Budi Santoso'));
    }
}
