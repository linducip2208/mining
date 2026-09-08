<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjDocumentImporter;

class BfjLetterImportTest extends AdminFlowTestCase
{
    public function test_letter_tokens_parsed_flexibly(): void
    {
        $a = BfjDocumentImporter::normalizeLetter(['NOMOR SURAT' => '001/SP-BFJ/I/2026', 'TANGGAL' => '05/01/2026', 'PERIHAL' => 'X']);
        $this->assertSame('001', $a['normalized']['tokens']['seq']);
        $this->assertSame('BFJ', $a['normalized']['tokens']['company']);
        $b = BfjDocumentImporter::normalizeLetter(['NOMOR SURAT' => '021/BAST/BFJ-TBS/VII/2026', 'TANGGAL' => '10/07/2026', 'PERIHAL' => 'Y']);
        $this->assertSame('TBS', $b['normalized']['tokens']['counterparty']);
    }
}
