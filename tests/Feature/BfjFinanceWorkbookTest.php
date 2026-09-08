<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjClassifier;
use Tests\TestCase;

class BfjFinanceWorkbookTest extends TestCase
{
    public function test_detects_finance_workbook(): void
    {
        $r = BfjClassifier::classifyFile('Laporan Keuangan Bulan Agustus 2026.xlsx', ['Arus Kas', 'Rekap']);
        $this->assertSame('FINANCE', $r['type']);
    }
}
