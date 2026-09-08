<?php

namespace Tests\Feature;

use App\Models\LegacyImportBatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyImportSheet;
use App\Services\Bfj\BfjReconciler;

class BfjFinanceReconciliationTest extends AdminFlowTestCase
{
    public function test_opening_plus_inflow_minus_outflow(): void
    {
        $b = LegacyImportBatch::create(['file_name' => 'f.xlsx', 'file_hash' => uniqid(), 'mode' => 'RECONCILIATION_ONLY', 'status' => 'IMPORTED']);
        $s = LegacyImportSheet::create(['batch_id' => $b->id, 'sheet_name' => 'Arus Kas', 'detected_type' => 'FINANCE', 'confidence' => 80, 'row_count' => 2, 'is_summary' => false, 'action' => 'IMPORT']);
        LegacyImportRow::create(['sheet_id' => $s->id, 'row_number' => 2, 'source' => [], 'normalized' => ['date' => '2026-08-02', 'inflow' => 1000000, 'outflow' => 0, 'flow_type' => 'OPERATING'], 'fingerprint' => uniqid(), 'status' => 'IMPORTED']);
        LegacyImportRow::create(['sheet_id' => $s->id, 'row_number' => 3, 'source' => [], 'normalized' => ['date' => '2026-08-03', 'inflow' => 0, 'outflow' => 400000, 'flow_type' => 'OPERATING'], 'fingerprint' => uniqid(), 'status' => 'IMPORTED']);
        $results = BfjReconciler::reconcile($b);
        $this->assertTrue(collect($results)->where('scope', 'FINANCE')->isNotEmpty());
    }
}
