<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRowFingerprint;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Services\ImportService;
use App\Support\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use Concerns\BuildsSalesFlow, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    protected function makeXlsx(string $storagePath, array $sheets, bool $macro = false): string
    {
        $full = Storage::disk('local')->path($storagePath);
        @mkdir(dirname($full), 0777, true);
        $zip = new \ZipArchive;
        $zip->open($full, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        $sheetXmlParts = '';
        $shared = [];
        $sharedIndex = [];
        $sheetEntries = '';
        foreach ($sheets as $i => [$name, $rows]) {
            $sheetEntries .= '<sheet name="'.htmlspecialchars($name).'" sheetId="'.($i + 1).'" r:id="rIdS'.($i + 1).'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>';
            $cells = '';
            foreach ($rows as $rowIdx => $row) {
                $cells .= '<row r="'.($rowIdx + 1).'">';
                foreach ($row as $colIdx => $value) {
                    $ref = SpreadsheetReader::columnName($colIdx).($rowIdx + 1);
                    if (is_numeric($value) && ! in_array($value, ['01', '02'], true)) {
                        $cells .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                    } else {
                        if (! isset($sharedIndex[$value])) {
                            $sharedIndex[$value] = count($shared);
                            $shared[] = $value;
                        }
                        $cells .= '<c r="'.$ref.'" t="s"><v>'.$sharedIndex[$value].'</v></c>';
                    }
                }
                $cells .= '</row>';
            }
            $sheetXmlParts .= 'xl/worksheets/sheet'.($i + 1).'.xml';
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$cells.'</sheetData></worksheet>');
        }
        $sharedXml = '';
        foreach ($shared as $s) {
            $sharedXml .= '<si><t>'.htmlspecialchars($s).'</t></si>';
        }
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($shared).'" uniqueCount="'.count($shared).'">'.$sharedXml.'</sst>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheetEntries.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.collect($sheets)->values()->map(fn ($s, $i) => '<Relationship Id="rIdS'.($i + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.($i + 1).'.xml"/>')->implode('').'</Relationships>');
        if ($macro) {
            $zip->addFromString('xl/vbaProject.bin', 'fake-vba');
        }
        $zip->close();

        return $full;
    }

    public function test_xlsx_file_reads_natively(): void
    {
        $this->makeXlsx('imports/test-native.xlsx', [
            ['MASTER', [['KODE', 'NAMA SPAREPART'], ['SPR-X1', 'Filter Oli']]],
        ]);
        $batch = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => 'imports/test-native.xlsx', 'file_ext' => 'xlsx', 'status' => 'UPLOADED']);
        $data = ImportService::readWorkbook($batch);
        $this->assertEquals(['KODE', 'NAMA SPAREPART'], $data['headers']);
        $this->assertEquals('SPR-X1', $data['rows'][0]['KODE']);
    }

    public function test_macro_enabled_xlsx_rejected(): void
    {
        $this->makeXlsx('imports/test-macro.xlsx', [
            ['MASTER', [['KODE'], ['SPR-X1']]],
        ], macro: true);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('macro');
        SpreadsheetReader::read(Storage::disk('local')->path('imports/test-macro.xlsx'));
    }

    public function test_multi_sheet_select_by_name(): void
    {
        $this->makeXlsx('imports/test-multi.xlsx', [
            ['REGISTER SURAT', [['NOMOR SURAT'], ['001/SP/2026']]],
            ['MASTER SPAREPART', [['KODE', 'NAMA SPAREPART'], ['SPR-M1', 'Bearing']]],
        ]);
        $batch = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => 'imports/test-multi.xlsx', 'file_ext' => 'xlsx', 'sheet' => 'MASTER SPAREPART', 'status' => 'UPLOADED']);
        $data = ImportService::readWorkbook($batch);
        $this->assertEquals('SPR-M1', $data['rows'][0]['KODE']);

        $sheets = SpreadsheetReader::sheets(Storage::disk('local')->path('imports/test-multi.xlsx'));
        $this->assertEquals(['REGISTER SURAT', 'MASTER SPAREPART'], $sheets);
    }

    public function test_auto_header_alias_mapping(): void
    {
        $headers = ['No. Surat', 'TANGGAL', 'Perihal'];
        $map = ImportService::autoMap($headers, 'letter_register');
        $this->assertEquals('number', $map['No. Surat']);
        $this->assertEquals('date', $map['TANGGAL']);
        $this->assertEquals('subject', $map['Perihal']);

        $headers2 = ['KODE BARANG', 'NAMA BARANG', 'QTY', 'STOK'];
        $map2 = ImportService::autoMap($headers2, 'sparepart_master');
        $this->assertEquals('code', $map2['KODE BARANG']);
        $this->assertEquals('name', $map2['NAMA BARANG']);
        // QTY/STOK are not sparepart_master fields → null (user overrides manually)
        $this->assertNull($map2['QTY']);
        $this->assertNull($map2['STOK']);

        $headers3 = ['KODE SPAREPART', 'QTY', 'COST'];
        $map3 = ImportService::autoMap($headers3, 'opening_stock');
        $this->assertEquals('code', $map3['KODE SPAREPART']);
        $this->assertEquals('qty', $map3['QTY']);
        $this->assertEquals('cost', $map3['COST']);
    }

    public function test_indonesian_number_formats(): void
    {
        $this->assertEquals(12000000.0, ImportService::parseNumber('Rp 12.000.000'));
        $this->assertEquals(12000000.0, ImportService::parseNumber('12.000.000'));
        $this->assertEquals(12000000.0, ImportService::parseNumber('12,000,000'));
        $this->assertEquals(700.0, ImportService::parseNumber('700'));
        $this->assertEquals(2000.0, ImportService::parseNumber('2.000'));
        $this->assertEquals(2.5, ImportService::parseNumber('2,5'));
        $this->assertEquals(2.5, ImportService::parseNumber('2.5'));
        $this->assertEquals(-1500.0, ImportService::parseNumber('(1.500)'));
        $this->assertNull(ImportService::parseNumber('abc'));
    }

    public function test_date_formats_and_ambiguity_warning(): void
    {
        $this->assertEquals('2024-03-05', ImportService::parseDateWithWarning('2024-03-05')['date']);
        $this->assertFalse(ImportService::parseDateWithWarning('2024-03-05')['ambiguous']);
        // dd/mm/YYYY (ID locale): 05/03/2024 = 5 Maret 2024 — unambiguous
        $this->assertEquals('2024-03-05', ImportService::parseDateWithWarning('05/03/2024')['date']);
        // both day and month ≤ 12 → ambiguous, flagged for WARNING
        $this->assertTrue(ImportService::parseDateWithWarning('03/05/2024')['ambiguous']);
        // Excel serial 45356 = 2024-03-05
        $this->assertEquals('2024-03-05', ImportService::parseDateWithWarning('45356')['date']);
        $this->assertNull(ImportService::parseDateWithWarning('bukan tanggal'));
    }

    public function test_file_hash_and_force_warning(): void
    {
        Storage::disk('local')->put('imports/h1.csv', "KODE,NAMA SPAREPART\nSPR-H1,Filter\n");
        $batch = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => 'imports/h1.csv', 'file_hash' => hash_file('sha256', Storage::disk('local')->path('imports/h1.csv')), 'file_ext' => 'csv', 'column_map' => ['KODE' => 'code', 'NAMA SPAREPART' => 'name'], 'status' => 'VALIDATED']);

        $this->assertFalse(ImportService::fileSeenBefore($batch->file_hash));
        ImportService::execute($batch);
        $this->assertTrue(ImportService::fileSeenBefore($batch->file_hash));

        // second batch with identical content is flagged
        Storage::disk('local')->put('imports/h2.csv', Storage::disk('local')->get('imports/h1.csv'));
        $batch2 = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => 'imports/h2.csv', 'file_hash' => hash_file('sha256', Storage::disk('local')->path('imports/h2.csv')), 'file_ext' => 'csv', 'column_map' => ['KODE' => 'code', 'NAMA SPAREPART' => 'name'], 'status' => 'VALIDATED']);
        $result = ImportService::validate($batch2, $batch2->column_map);
        $this->assertStringContainsString('pernah diimport', $result['errors'][0]['error']);
    }

    public function test_row_fingerprint_prevents_duplicate_import(): void
    {
        $csv = "KODE,NAMA SPAREPART\nSPR-F1,Filter\n";
        Storage::disk('local')->put('imports/f1.csv', $csv);
        $batch = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => 'imports/f1.csv', 'file_ext' => 'csv', 'column_map' => ['KODE' => 'code', 'NAMA SPAREPART' => 'name'], 'status' => 'VALIDATED']);
        [$imported] = ImportService::execute($batch);
        $this->assertEquals(1, $imported);

        // re-import identical rows in a NEW batch → all skipped via fingerprint
        Storage::disk('local')->put('imports/f2.csv', $csv);
        $batch2 = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => 'imports/f2.csv', 'file_ext' => 'csv', 'column_map' => ['KODE' => 'code', 'NAMA SPAREPART' => 'name'], 'status' => 'VALIDATED']);
        [$imported2, $skipped2] = ImportService::execute($batch2);
        $this->assertEquals(0, $imported2);
        $this->assertEquals(1, $skipped2);
        $this->assertEquals(1, Item::where('code', 'SPR-F1')->count());
        $this->assertGreaterThan(0, ImportRowFingerprint::where('type', 'sparepart_master')->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        Storage::disk('local')->put('imports/dry.csv', "KODE,NAMA SPAREPART\nSPR-DRY,Filter\n");
        $batch = ImportBatch::create(['type' => 'sparepart_master', 'file_name' => 'imports/dry.csv', 'file_ext' => 'csv', 'column_map' => ['KODE' => 'code', 'NAMA SPAREPART' => 'name'], 'status' => 'VALIDATED']);
        [$imported, $skipped] = ImportService::execute($batch, dryRun: true);

        $this->assertEquals(1, $imported);
        $this->assertNull(Item::where('code', 'SPR-DRY')->first());
        $this->assertEquals(0, ImportRowFingerprint::count());
        $this->assertNotEquals('IMPORTED', $batch->fresh()->status);
    }

    public function test_import_modes_recorded_and_opening_ar_posts_journal(): void
    {
        $customer = $this->makeCustomer();
        Storage::disk('local')->put('imports/inv.csv', "NOMOR INVOICE,TANGGAL,CUSTOMER,TOTAL\nINV-AR-1,2024-03-01,{$customer->name},2500000\n");
        $batch = ImportBatch::create([
            'type' => 'legacy_invoice', 'file_name' => 'imports/inv.csv', 'file_ext' => 'csv',
            'mode' => 'OPENING_AR', 'company_id' => $this->co->id,
            'column_map' => ['NOMOR INVOICE' => 'number', 'TANGGAL' => 'date', 'CUSTOMER' => 'customer', 'TOTAL' => 'total'],
            'status' => 'VALIDATED',
        ]);
        $before = JournalEntry::count();
        [$imported] = ImportService::execute($batch);
        $this->assertEquals(1, $imported);
        $this->assertEquals($before + 1, JournalEntry::count(), 'OPENING_AR harus posting jurnal');
        $journal = JournalEntry::where('source_type', 'OPENING_AR')->firstOrFail();
        $this->assertEquals(2500000.0, (float) $journal->total_debit);
    }

    public function test_register_only_mode_never_posts_journal(): void
    {
        $customer = $this->makeCustomer();
        Storage::disk('local')->put('imports/inv2.csv', "NOMOR INVOICE,TANGGAL,CUSTOMER,TOTAL\nINV-REG-1,2024-03-01,{$customer->name},2500000\n");
        $batch = ImportBatch::create([
            'type' => 'legacy_invoice', 'file_name' => 'imports/inv2.csv', 'file_ext' => 'csv',
            'mode' => 'REGISTER_ONLY', 'company_id' => $this->co->id,
            'column_map' => ['NOMOR INVOICE' => 'number', 'TANGGAL' => 'date', 'CUSTOMER' => 'customer', 'TOTAL' => 'total'],
            'status' => 'VALIDATED',
        ]);
        $before = JournalEntry::count();
        ImportService::execute($batch);
        $this->assertEquals($before, JournalEntry::count(), 'REGISTER_ONLY tidak boleh posting jurnal');
    }
}
