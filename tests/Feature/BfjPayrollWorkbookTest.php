<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjClassifier;
use App\Services\Bfj\BfjPayrollImporter;
use Tests\TestCase;

class BfjPayrollWorkbookTest extends TestCase
{
    public function test_detects_payroll_and_ignores_rekap(): void
    {
        $r = BfjClassifier::classifyFile('PERHITUNGAN GAJI KARYAWAN AGUSTUS 2026.xlsx', ['Budi', 'REKAP']);
        $this->assertSame('PAYROLL', $r['type']);
        $this->assertTrue(BfjPayrollImporter::isSummarySheet('REKAP'));
        $this->assertTrue(BfjPayrollImporter::isSummarySheet('Copy of REKAP'));
        $this->assertTrue(BfjPayrollImporter::isSummarySheet('SLIP GAJI'));
    }
}
