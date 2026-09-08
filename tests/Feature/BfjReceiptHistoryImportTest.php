<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjDocumentImporter;
use Tests\TestCase;

class BfjReceiptHistoryImportTest extends TestCase
{
    public function test_receipt_history_mode_links_invoice_ref(): void
    {
        $r = BfjDocumentImporter::normalizeReceipt(['NOMOR KWITANSI' => 'KW/1', 'TANGGAL' => '06/08/2026', 'TERKAIT INVOICE' => 'INV/1', 'NOMINAL' => '300000', 'METODE BAYAR' => 'Transfer']);
        $this->assertSame('INV/1', $r['normalized']['invoice_ref']);
        $this->assertSame('TRANSFER', $r['normalized']['method']);
    }
}
