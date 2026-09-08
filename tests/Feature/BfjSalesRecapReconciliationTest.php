<?php

namespace Tests\Feature;

use App\Models\LegacyImportBatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyImportSheet;
use App\Services\Bfj\BfjReconciler;

class BfjSalesRecapReconciliationTest extends AdminFlowTestCase
{
    public function test_detail_vs_recap_variance_reported(): void
    {
        $b = LegacyImportBatch::create(['file_name' => 'x.xlsx', 'file_hash' => uniqid(), 'mode' => 'HISTORY_ONLY', 'status' => 'IMPORTED']);
        $d = LegacyImportSheet::create(['batch_id' => $b->id, 'sheet_name' => '1 September 2026', 'detected_type' => 'SALES', 'confidence' => 90, 'row_count' => 1, 'is_summary' => false, 'action' => 'IMPORT']);
        LegacyImportRow::create(['sheet_id' => $d->id, 'row_number' => 2, 'source' => [], 'normalized' => ['transaction_date' => '2026-09-01', 'volume_m3' => 10, 'material' => 'ABU BATU'], 'fingerprint' => uniqid(), 'status' => 'IMPORTED']);
        $s = LegacyImportSheet::create(['batch_id' => $b->id, 'sheet_name' => 'REKAP BFJ', 'detected_type' => 'SALES_RECAP_MATRIX', 'confidence' => 80, 'row_count' => 1, 'is_summary' => true, 'action' => 'RECONCILE_ONLY']);
        LegacyImportRow::create(['sheet_id' => $s->id, 'row_number' => 2, 'source' => [], 'normalized' => ['dimension' => '2026-09-01|ABU BATU', 'date' => '2026-09-01', 'material' => 'ABU BATU', 'volume_total' => 12], 'fingerprint' => uniqid(), 'status' => 'IMPORTED']);

        $results = BfjReconciler::reconcile($b);
        $sales = collect($results)->where('scope', 'SALES');
        $this->assertTrue($sales->isNotEmpty());
        $this->assertSame('VARIANCE', $sales->first()['status']);
    }
}
