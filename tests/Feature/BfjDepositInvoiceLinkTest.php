<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjDepositImporter;
use Tests\TestCase;

class BfjDepositInvoiceLinkTest extends TestCase
{
    public function test_legacy_invoice_stored_never_guessed(): void
    {
        $r = BfjDepositImporter::normalize(['TANGGAL' => '05/08/2026', 'INVOICE' => 'INV NO 006', 'DEPOSIT' => '500000']);
        $this->assertSame('INV NO 006', $r['normalized']['legacy_invoice']);
    }
}
