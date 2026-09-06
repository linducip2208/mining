<?php

namespace Tests\Unit;

use App\Support\HumanLabel;
use PHPUnit\Framework\TestCase;

class FormLabelTest extends TestCase
{
    public function test_database_fields_have_form_labels(): void
    {
        $this->assertSame('Site', HumanLabel::label('site_id'));
        $this->assertSame('Customer', HumanLabel::label('customer_id'));
        $this->assertSame('Unit / Peralatan', HumanLabel::label('equipment_id'));
        $this->assertSame('Gudang', HumanLabel::label('warehouse_id'));
    }
}
