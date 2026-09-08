<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjSparepartImporter;
use Tests\TestCase;

class BfjSparepartIssueTest extends TestCase
{
    public function test_issue_maps_equipment_user_purpose(): void
    {
        $r = BfjSparepartImporter::normalizeMovement([
            'TANGGAL' => '05/08/2026', 'KODE' => 'SPR-001', 'SATUAN' => 'PCS',
            'UNIT/ALAT' => 'HINO 500', 'QTY KELUAR' => '2', 'PEMAKAI' => 'Driver A', 'KEPERLUAN' => 'GANTI BAN',
        ], 'ISSUE');
        $this->assertSame('HINO 500', $r['normalized']['equipment']);
        $this->assertSame('Driver A', $r['normalized']['user']);
        $this->assertSame('GANTI BAN', $r['normalized']['purpose']);
    }
}
