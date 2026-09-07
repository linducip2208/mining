<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Services\ImportService;
use Illuminate\Support\Facades\Storage;

class LegacyInvoiceImportTest extends AdminFlowTestCase
{
    public function test_legacy_invoice_recorded_without_journal(): void
    {
        $customer = $this->makeCustomer();
        $path = 'imports/test-'.uniqid().'.csv';
        Storage::disk('local')->put($path, "NOMOR INVOICE,TANGGAL,CUSTOMER,TOTAL\nINV-OLD-001,2024-03-01,{$customer->name},2500000\n");
        $batch = ImportBatch::create(['type' => 'legacy_invoice', 'file_name' => $path, 'status' => 'UPLOADED']);
        $map = ['NOMOR INVOICE' => 'number', 'TANGGAL' => 'date', 'CUSTOMER' => 'customer', 'TOTAL' => 'total'];

        $result = ImportService::validate($batch, $map);
        $this->assertEquals(1, $result['valid']);
        $batch->update(['column_map' => $map, 'status' => 'VALIDATED']);
        [$imported] = ImportService::execute($batch);
        $this->assertEquals(1, $imported);

        $invoice = Invoice::where('number', 'INV-OLD-001')->firstOrFail();
        $this->assertEquals('LEGACY_IMPORT', $invoice->source);
        $this->assertNull($invoice->journal_entry_id);
        $this->assertEquals(0, JournalEntry::count());
    }
}
