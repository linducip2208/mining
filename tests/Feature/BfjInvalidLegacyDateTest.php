<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjParsers;
use Tests\TestCase;

class BfjInvalidLegacyDateTest extends TestCase
{
    public function test_invalid_dates_never_silently_rolled(): void
    {
        $this->assertSame('INVALID_DATE', BfjParsers::parseDate('99/99/2026')['error']);
        $this->assertSame('FORMULA_ERROR', BfjParsers::parseDate('#ERROR!')['error']);
        $this->assertNotNull(BfjParsers::parseDate('02/09/2026')['value']);
        $this->assertNotNull(BfjParsers::parseDate(46000)['value']);
    }
}
