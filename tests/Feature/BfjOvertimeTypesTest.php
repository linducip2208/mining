<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjPayrollImporter;
use Tests\TestCase;

class BfjOvertimeTypesTest extends TestCase
{
    public function test_supports_all_legacy_overtime_types(): void
    {
        $this->assertContains('LEMBUR_PAGI', BfjPayrollImporter::OVERTIME_TYPES);
        $this->assertContains('LEMBUR_MALAM', BfjPayrollImporter::OVERTIME_TYPES);
        $this->assertContains('LEMBUR_HARI_LIBUR', BfjPayrollImporter::OVERTIME_TYPES);
        $r = BfjPayrollImporter::normalizeDay(['MULAI' => '08:00', 'SELESAI' => '17:00', 'PAGI' => '1', 'MALAM' => '2']);
        $this->assertArrayHasKey('LEMBUR_PAGI', $r['overtime']);
        $this->assertArrayHasKey('LEMBUR_MALAM', $r['overtime']);
    }
}
