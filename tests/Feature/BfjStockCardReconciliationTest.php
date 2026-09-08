<?php

namespace Tests\Feature;

use App\Models\LegacyImportBatch;
use App\Models\LegacyImportRow;
use App\Models\LegacyImportSheet;
use App\Services\Bfj\BfjReconciler;

class BfjStockCardReconciliationTest extends AdminFlowTestCase
{
    public function test_stock_card_reconciled(): void
    {
        $b = LegacyImportBatch::create(['file_name' => 's.xlsx', 'file_hash' => uniqid(), 'mode' => 'HISTORY_ONLY', 'status' => 'IMPORTED']);
        $s = LegacyImportSheet::create(['batch_id' => $b->id, 'sheet_name' => 'KARTU STOK', 'detected_type' => 'STOCK_CARD', 'confidence' => 80, 'row_count' => 1, 'is_summary' => false, 'action' => 'IMPORT']);
        LegacyImportRow::create(['sheet_id' => $s->id, 'row_number' => 2, 'source' => [], 'normalized' => ['code' => 'SPR-001', 'qty' => 5, 'movement' => 'RECEIPT'], 'fingerprint' => uniqid(), 'status' => 'IMPORTED']);
        $this->assertTrue(collect(BfjReconciler::reconcile($b))->where('scope', 'SPAREPART')->isNotEmpty());
    }
}
