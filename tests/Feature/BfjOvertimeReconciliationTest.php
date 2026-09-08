<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjPayrollImporter;
use Tests\TestCase;

class BfjOvertimeReconciliationTest extends TestCase
{
    public function test_flags_rule_difference(): void
    {
        $r = BfjPayrollImporter::normalizeDay(['MULAI' => '08:00', 'SELESAI' => '17:00', 'JUMLAH' => '5']);
        $this->assertNotEmpty($r['issues']);
        $ok = BfjPayrollImporter::normalizeDay(['MULAI' => '08:00', 'SELESAI' => '17:00', 'JUMLAH' => '8']);
        $this->assertEmpty($ok['issues']);
    }
}
