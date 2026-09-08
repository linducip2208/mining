<?php

namespace Tests\Feature;

use App\Models\LegacyImportBatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyImportSheet;
use App\Services\Bfj\BfjReconciler;

class BfjDepositBalanceReconciliationTest extends AdminFlowTestCase
{
    public function test_deposit_entries_reconciled_per_customer(): void
    {
        $b = LegacyImportBatch::create(['file_name' => 'd.xlsx', 'file_hash' => uniqid(), 'mode' => 'HISTORY_ONLY', 'status' => 'IMPORTED']);
        $s = LegacyImportSheet::create(['batch_id' => $b->id, 'sheet_name' => 'Customer A', 'detected_type' => 'CUSTOMER_DEPOSIT', 'confidence' => 80, 'row_count' => 2, 'is_summary' => false, 'action' => 'IMPORT']);
        LegacyImportRow::create(['sheet_id' => $s->id, 'row_number' => 2, 'source' => [], 'normalized' => ['customer' => 'CUSTOMER A', 'type' => 'DEPOSIT', 'amount' => 1500000], 'fingerprint' => uniqid(), 'status' => 'IMPORTED']);
        LegacyImportRow::create(['sheet_id' => $s->id, 'row_number' => 3, 'source' => [], 'normalized' => ['customer' => 'CUSTOMER A', 'type' => 'CONSUMPTION', 'amount' => 300000], 'fingerprint' => uniqid(), 'status' => 'IMPORTED']);
        $results = BfjReconciler::reconcile($b);
        $dep = collect($results)->firstWhere('scope', 'DEPOSIT');
        $this->assertNotNull($dep);
        $this->assertEquals(1200000, $dep['erp_total']);
    }
}
