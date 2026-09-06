<?php

namespace Tests\Unit;

use App\Support\HumanLabel;
use App\Support\PermissionLabel;
use App\Support\StatusLabel;
use PHPUnit\Framework\TestCase;

class MachineKeyLeakTest extends TestCase
{
    public function test_common_machine_values_are_never_used_as_visible_labels(): void
    {
        foreach (['payroll.pph21_rate', 'site_id', 'created_at', 'approval_status'] as $value) {
            $this->assertStringNotContainsString('_', HumanLabel::label($value));
            $this->assertStringNotContainsString('.', HumanLabel::label($value));
        }
        $this->assertSame('Setujui Purchase Order', PermissionLabel::label('purchase_order.approve'));
        $this->assertSame('Menunggu Persetujuan', StatusLabel::label('PENDING_APPROVAL'));
    }
}
