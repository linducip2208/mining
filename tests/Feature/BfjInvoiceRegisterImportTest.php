<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjDocumentImporter;
use Tests\TestCase;

class BfjInvoiceRegisterImportTest extends TestCase
{
    public function test_status_normalization_preserves_source(): void
    {
        $paid = BfjDocumentImporter::normalizeInvoice(['NOMOR INVOICE' => 'INV/1', 'TANGGAL' => null, 'TANGGAL' => '05/08/2026', 'TOTAL' => '300000', 'STATUS' => 'Lunas']);
        $this->assertSame('PAID', $paid['normalized']['status']);
        $this->assertSame('Lunas', $paid['normalized']['status_raw']);
        $out = BfjDocumentImporter::normalizeInvoice(['NOMOR INVOICE' => 'INV/2', 'TANGGAL' => '06/08/2026', 'TOTAL' => '100', 'STATUS' => 'BELUM LUNAS']);
        $this->assertSame('OUTSTANDING', $out['normalized']['status']);
    }
}
