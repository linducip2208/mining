<?php

namespace Tests\Unit;

use App\Support\StatusLabel;
use PHPUnit\Framework\TestCase;

class StatusLabelTest extends TestCase
{
    public function test_statuses_are_presented_in_indonesian(): void
    {
        $this->assertSame('Menunggu Persetujuan', StatusLabel::label('PENDING_APPROVAL'));
        $this->assertSame('Sedang Diproses', StatusLabel::label('IN_PROGRESS'));
        $this->assertSame('Dibayar Sebagian', StatusLabel::label('PARTIALLY_PAID'));
        $this->assertSame('Dalam Pemeliharaan', StatusLabel::label('UNDER_MAINTENANCE'));
    }
}
