<?php

namespace Tests\Unit;

use App\Support\ValidationAttributes;
use PHPUnit\Framework\TestCase;

class ValidationLanguageTest extends TestCase
{
    public function test_validation_attributes_are_indonesian(): void
    {
        $this->assertSame('Site', ValidationAttributes::label('site_id'));
        $this->assertSame('Tanggal Mulai', ValidationAttributes::label('date_from'));
        $this->assertSame('Kata Sandi', ValidationAttributes::label('password'));
    }
}
