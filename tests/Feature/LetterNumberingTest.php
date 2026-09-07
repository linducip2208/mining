<?php

namespace Tests\Feature;

use App\Models\LetterType;
use App\Services\LetterService;
use App\Services\NumberingService;

class LetterNumberingTest extends AdminFlowTestCase
{
    public function test_format_tokens_render_like_company_example(): void
    {
        $type = LetterType::where('code', 'SP')->first();
        $type->update(['numbering_format' => '{SEQ:3}/{TYPE}-{COMPANY}/{MONTH_ROMAN}/{YEAR}', 'reset_period' => 'NEVER']);
        LetterService::ensureNumbering($type);

        $number = NumberingService::generate('LETTER_SP', null, null, [
            'TYPE' => 'SP', 'COMPANY' => 'BFJ',
        ]);

        $this->assertMatchesRegularExpression('#^001/SP-BFJ/[IVX]+/\d{4}$#', $number);
    }

    public function test_month_roman_token(): void
    {
        $this->assertEquals('I', NumberingService::romanMonth(1));
        $this->assertEquals('IX', NumberingService::romanMonth(9));
        $this->assertEquals('XII', NumberingService::romanMonth(12));
    }

    public function test_sequence_increments_per_type(): void
    {
        foreach (['SK', 'HRD'] as $code) {
            $type = LetterType::where('code', $code)->first();
            $type->update(['numbering_format' => '{SEQ:3}/{TYPE}-{COMPANY}/{MONTH_ROMAN}/{YEAR}', 'reset_period' => 'NEVER']);
            \App\Services\LetterService::ensureNumbering($type);
        }
        $a = NumberingService::generate('LETTER_SK', null, null, ['TYPE' => 'SK', 'COMPANY' => 'BFJ']);
        $b = NumberingService::generate('LETTER_SK', null, null, ['TYPE' => 'SK', 'COMPANY' => 'BFJ']);
        $c = NumberingService::generate('LETTER_HRD', null, null, ['TYPE' => 'HRD', 'COMPANY' => 'BFJ']);

        $this->assertNotEquals($a, $b);
        $this->assertStringContainsString('/SK-BFJ/', $b);
        $this->assertStringContainsString('/HRD-BFJ/', $c);
        $this->assertStringStartsWith('001/', $c);
    }
}
