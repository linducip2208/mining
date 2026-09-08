<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BfjLetterPatternVariantTest extends TestCase
{
    #[DataProvider('variants')]
    public function test_pattern_variants(string $number): void
    {
        $this->assertNotNull(BfjNormalizer::parseLetterNumber($number), $number);
    }

    public static function variants(): array
    {
        return [
            ['001/SP-BFJ/I/2026'], ['002/HRD-BFJ/II/2026'], ['013/INT-BFJ/IV/2026'],
            ['014/BA/BFJ/V/2026'], ['021/BAST/BFJ-TBS/VII/2026'],
        ];
    }
}
