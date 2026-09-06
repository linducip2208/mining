<?php

namespace Tests\Unit;

use App\Support\PermissionLabel;
use PHPUnit\Framework\TestCase;

class PermissionLabelTest extends TestCase
{
    public function test_permission_codes_are_presented_as_actions(): void
    {
        $this->assertSame('Setujui Purchase Order', PermissionLabel::label('purchase_order.approve'));
        $this->assertSame('Posting Jurnal', PermissionLabel::label('journal.post'));
        $this->assertSame('Reset Password Pengguna', PermissionLabel::label('user.reset_password'));
    }
}
