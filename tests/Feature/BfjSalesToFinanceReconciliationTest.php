<?php

namespace Tests\Feature;

use App\Models\LegacyImportBatch;
use App\Services\Bfj\BfjReconciler;
use Tests\TestCase;

class BfjSalesToFinanceReconciliationTest extends TestCase
{
    public function test_sales_finance_link_hint(): void
    {
        $h = BfjReconciler::crossFileHints(new LegacyImportBatch(['id' => 1]), new LegacyImportBatch(['id' => 2]));
        $this->assertSame('CROSS_FILE', $h[0]['scope']);
    }
}
