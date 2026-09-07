<?php

namespace Tests\Feature;

use App\Services\NumberToWordsService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndonesianTerbilangTest extends TestCase
{
    public function test_spec_example_388500000(): void
    {
        $this->assertEquals(
            'Tiga Ratus Delapan Puluh Delapan Juta Lima Ratus Ribu Rupiah',
            NumberToWordsService::rupiah(388500000)
        );
    }

    #[DataProvider('cases')]
    public function test_cases(int|float $amount, string $expected): void
    {
        $this->assertEquals($expected, NumberToWordsService::rupiah($amount));
    }

    public static function cases(): array
    {
        return [
            [0, 'Nol Rupiah'],
            [1, 'Satu Rupiah'],
            [11, 'Sebelas Rupiah'],
            [19, 'Sembilan Belas Rupiah'],
            [100, 'Seratus Rupiah'],
            [1000, 'Seribu Rupiah'],
            [15000, 'Lima Belas Ribu Rupiah'],
            [1000000, 'Satu Juta Rupiah'],
            [2500000, 'Dua Juta Lima Ratus Ribu Rupiah'],
            [12000000, 'Dua Belas Juta Rupiah'],
            [1000000000, 'Satu Miliar Rupiah'],
        ];
    }
}
