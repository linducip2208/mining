<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjPayrollImporter;
use Tests\TestCase;

class BfjNormalHoursTest extends TestCase
{
    public function test_recalculates_normal_hours_with_break(): void
    {
        $r = BfjPayrollImporter::normalizeDay(['MULAI' => '08:00', 'SELESAI' => '17:00', 'JUMLAH' => '8']);
        $this->assertEqualsWithDelta(8.0, $r['normal_hours'], 0.01);
        // different schedule 08:00-16:00 with 60min break = 7h
        $r2 = BfjPayrollImporter::normalizeDay(['MULAI' => '08:00', 'SELESAI' => '16:00', 'JUMLAH' => '7'], 480, 960, 60);
        $this->assertEqualsWithDelta(7.0, $r2['normal_hours'], 0.01);
    }
}
