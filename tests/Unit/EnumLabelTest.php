<?php

namespace Tests\Unit;

use App\Support\HumanLabel;
use PHPUnit\Framework\TestCase;

class EnumLabelTest extends TestCase
{
    public function test_acronyms_and_fields_have_safe_fallback_labels(): void
    {
        $this->assertSame('Tarif PPh 21', HumanLabel::label('pph21_rate'));
        $this->assertSame('Site', HumanLabel::label('site_id'));
        $this->assertSame('Dibuat Pada', HumanLabel::label('created_at'));
    }
}
