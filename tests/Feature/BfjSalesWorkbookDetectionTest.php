<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjClassifier;
use Tests\TestCase;

class BfjSalesWorkbookDetectionTest extends TestCase
{
    public function test_classifies_sales_workbook_by_filename(): void
    {
        $r = BfjClassifier::classifyFile('Penjualan September 2026.xlsx', ['1 September 2026', 'REKAP BFJ']);
        $this->assertSame('SALES', $r['type']);
    }

    public function test_daily_sheet_scores_high_and_rekap_is_summary(): void
    {
        $daily = BfjClassifier::classifySheet('3 September 2026', ['NO DO', 'NAMA SOPIR']);
        $this->assertSame('SALES', $daily['type']);
        $this->assertFalse($daily['is_summary']);

        $rekap = BfjClassifier::classifySheet('REKAP BFJ', []);
        $this->assertTrue($rekap['is_summary']);
    }
}
