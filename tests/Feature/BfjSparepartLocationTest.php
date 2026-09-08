<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjNormalizer;
use Tests\TestCase;

class BfjSparepartLocationTest extends TestCase
{
    public function test_dinding_is_valid_location(): void
    {
        $this->assertSame('DINDING', BfjNormalizer::normalizeLocation('dinding'));
        $this->assertSame('A01-01', BfjNormalizer::normalizeLocation('A01-01'));
    }
}
