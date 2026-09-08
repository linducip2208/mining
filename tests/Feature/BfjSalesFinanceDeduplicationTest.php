<?php

namespace Tests\Feature;

use App\Models\LegacyImportBatch;
use App\Services\Bfj\BfjReconciler;
use Tests\TestCase;

class BfjSalesFinanceDeduplicationTest extends TestCase
{
    public function test_cross_file_hint_presented_not_double_revenue(): void
    {
        $hints = BfjReconciler::crossFileHints(
            new LegacyImportBatch(['id' => 1]),
            new LegacyImportBatch(['id' => 2])
        );
        $this->assertSame('CROSS_FILE', $hints[0]['scope']);
        $this->assertStringContainsString('twice', strtolower($hints[0]['root_cause']));
    }
}
