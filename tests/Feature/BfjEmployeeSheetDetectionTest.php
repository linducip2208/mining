<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjClassifier;
use Tests\TestCase;

class BfjEmployeeSheetDetectionTest extends TestCase
{
    public function test_employee_sheet_signature(): void
    {
        $r = BfjClassifier::classifySheet('Budi - Operator', ['TANGGAL', 'MULAI', 'SELESAI', 'JUMLAH', 'LEMBUR PAGI', 'UNIT', 'KEGIATAN']);
        $this->assertSame('PAYROLL', $r['type']);
    }
}
