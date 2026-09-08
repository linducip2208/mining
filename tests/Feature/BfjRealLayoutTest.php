<?php

namespace Tests\Feature;

use App\Services\Bfj\BfjParsers;
use App\Services\Bfj\BfjSheetLayout;
use App\Support\SpreadsheetReader;
use Tests\TestCase;

class BfjRealLayoutTest extends TestCase
{
    private function grid(array $rows): array
    {
        $grid = [];
        foreach ($rows as $rn => $vals) {
            foreach ($vals as $c => $v) {
                $grid[$rn][$c] = ['v' => $v, 'f' => null, 'cached' => true];
            }
        }

        return $grid;
    }

    public function test_detects_offset_header_and_total_stop(): void
    {
        $grid = $this->grid([
            3 => [1 => 'LAPORAN PENJUALAN HARIAN PT. BARUS FAMILLY JAYA'],
            4 => [1 => 'Tanggal 1 September 2026'],
            6 => [1 => 'NO DO', 2 => 'NAMA SOPIR', 3 => 'NO POLIS', 4 => 'COSTUMER', 5 => 'JENIS MATERIAL', 6 => 'KUBIKASI TERJUAL', 7 => 'HARGA (Rp)', 8 => 'RITEL'],
            7 => [1 => '1', 2 => 'IPAN'],
            21 => [1 => '', 2 => '', 3 => '', 4 => 'TOTAL'],
        ]);
        $layout = BfjSheetLayout::analyze($grid, [], '1 September 2026');
        $this->assertSame(6, $layout['header_row']);
        $this->assertSame('SALES', $layout['domain']);
        $this->assertSame(21, $layout['stop_row']);
        $this->assertSame('Tanggal 1 September 2026', $layout['title']['date']);
    }

    public function test_single_column_merge_fill_only(): void
    {
        $grid = $this->grid([10 => [1 => '46089'], 11 => [], 12 => []]);
        $filled = BfjSheetLayout::forwardFill($grid, ['B10:B20', 'B13:D14']);
        $this->assertSame('46089', $filled[11][1]['v']);
        // multi-column merge B13:D14 must NOT duplicate values across columns
        $grid2 = $this->grid([13 => [1 => '-86600083']]);
        $filled2 = BfjSheetLayout::forwardFill($grid2, ['B13:D14']);
        $this->assertArrayNotHasKey(2, $filled2[13] ?? []);
    }

    public function test_column_letters_roundtrip(): void
    {
        $this->assertSame('A', SpreadsheetReader::columnName(0));
        $this->assertSame('Z', SpreadsheetReader::columnName(25));
        $this->assertSame('AA', SpreadsheetReader::columnName(26));
        [$c, $r] = SpreadsheetReader::splitRef('AB12');
        $this->assertSame([27, 12], [$c, $r]);
    }

    public function test_indonesian_dates_and_typos(): void
    {
        $this->assertSame('2026-08-03', BfjParsers::parseDate('3 Agustus 2026')['value']);
        $this->assertSame('2026-08-30', BfjParsers::parseDate('30 Agustsu 2026')['value']);
        $this->assertSame('2026-08-19', BfjParsers::parseDate('19/8/2026')['value']);
        $this->assertTrue(BfjParsers::periodMismatch('2027-07-30', 'PERIODE AGUSTUS 2026'));
        $this->assertFalse(BfjParsers::periodMismatch('2026-08-03', 'PERIODE AGUSTUS 2026'));
    }

    public function test_decimal_hour_times(): void
    {
        $this->assertSame(480, BfjParsers::timeToMinutes('8.0'));
        $this->assertSame(990, BfjParsers::timeToMinutes('16.5'));
        $this->assertSame(678, BfjParsers::timeToMinutes('11.3'));
        $this->assertSame(1020, BfjParsers::timeToMinutes('17:00'));
    }

    public function test_money_guard_rejects_text_with_digits(): void
    {
        $this->assertNull(BfjParsers::parseMoney('SISA SALDO PAK LASEN BULAN JULI 2026')['value']);
        $this->assertSame('NUMBER_PATTERN_VARIANCE', BfjParsers::parseMoney('Pembelian BBM 40 Derigen')['error']);
        $this->assertEquals(485082564, BfjParsers::parseMoney('4.85082564E8')['value']);
        $this->assertEquals(66275658, BfjParsers::parseMoney('Rp66,275,658')['value']);
    }
}
