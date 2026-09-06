<?php

namespace Tests\Unit;

use App\Support\HumanLabel;
use PHPUnit\Framework\TestCase;

class TableHeaderLabelTest extends TestCase
{
    public function test_export_and_table_fields_have_human_labels(): void
    {
        $this->assertSame('Nomor Referensi', HumanLabel::label('reference_no'));
        $this->assertSame('Output Bersih', HumanLabel::label('net_output'));
        $this->assertSame('Berat Kotor', HumanLabel::label('gross_weight'));
        $this->assertSame('Biaya per Unit', HumanLabel::label('unit_cost'));
    }
}
