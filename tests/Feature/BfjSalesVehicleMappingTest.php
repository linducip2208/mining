<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjNormalizer;
use Tests\TestCase;

class BfjSalesVehicleMappingTest extends TestCase
{
    public function test_plate_variants_normalize_but_keep_original(): void
    {
        $this->assertSame('BG 8506 DS', BfjNormalizer::normalizePlate('BG8506DS'));
        $this->assertSame('BG 8506 DS', BfjNormalizer::normalizePlate('bg 8506 ds'));
        $this->assertSame('BG 8506 DS', BfjNormalizer::normalizePlate('BG 8506 DS'));
    }
}
