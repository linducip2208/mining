<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjClassifier;
use Tests\TestCase;

class BfjStockReportReconciliationTest extends TestCase
{
    public function test_report_sheet_is_benchmark_only(): void
    {
        $r = BfjClassifier::classifySheet('LAPORAN STOK', ['STOK AWAL', 'TOTAL MASUK', 'TOTAL KELUAR', 'STOK AKHIR']);
        $this->assertTrue($r['is_summary']);
    }
}
