<?php

namespace App\Support;

/**
 * Minimal native BIFF8 (.xls, Excel 97-2003) reader — no external package.
 *
 * Only what the legacy import needs: text and numeric cells (RK / NUMBER /
 * LABELSST / LABEL / MULRK), first sheet or a named sheet via BOUNDSHEET.
 * Formulas are read as their cached values. Charts / VBA / drawings ignored.
 * VBA-bearing workbooks are refused at the OLE layer (vba storage present).
 */
final class XlsBiffReader
{
    private const BIFF_VERSION_8 = 0x0600;

    private const RECORD_EOF = 0x000A;

    private const RECORD_BOUNDSHEET = 0x0085;

    private const RECORD_SST = 0x00FC;

    private const RECORD_LABELSST = 0x00FD;

    private const RECORD_LABEL = 0x0204;

    private const RECORD_RK = 0x027E;

    private const RECORD_NUMBER = 0x0203;

    private const RECORD_MULRK = 0x00BD;

    private const RECORD_FORMULA = 0x0006;

    private const RECORD_STRING = 0x0207;

    private const RECORD_DIMENSIONS = 0x0200;

    private string $data;

    /** @var array<int,string> */
    private array $sst = [];

    /** @var array<int,array{name:string,offset:int}> */
    private array $sheets = [];

    private function __construct(string $data)
    {
        $this->data = $data;
    }

    public static function sheetNames(string $path): array
    {
        $reader = self::load($path);

        return array_map(fn ($s) => $s['name'], $reader->sheets);
    }

    public static function read(string $path, ?string $sheetName, int $maxRows): array
    {
        $reader = self::load($path);
        if ($reader->sheets === []) {
            throw new \RuntimeException('Tidak ada sheet di file XLS.');
        }
        $sheet = $reader->sheets[0];
        if ($sheetName !== null) {
            foreach ($reader->sheets as $s) {
                if (strcasecmp($s['name'], $sheetName) === 0) {
                    $sheet = $s;
                    break;
                }
            }
        }

        $grid = $reader->parseSheet($sheet['offset'], $maxRows);

        return $reader->gridToTable($grid, $maxRows);
    }

    // ---------- OLE compound document ----------

    private static function load(string $path): self
    {
        $raw = file_get_contents($path);
        if ($raw === false || strlen($raw) < 512) {
            throw new \RuntimeException('File XLS kosong atau tidak dapat dibaca.');
        }
        if (str_contains($raw, 'vbaProject') || str_contains($raw, '_VBA_PROJECT')) {
            throw new \RuntimeException('File XLS berisi macro VBA — tidak diizinkan. Simpan ulang sebagai .xlsx atau .csv.');
        }

        $data = self::oleExtractWorkbook($raw);
        if ($data === null) {
            throw new \RuntimeException('Workbook stream tidak ditemukan di file XLS.');
        }

        $reader = new self($data);
        $reader->parseGlobals();

        return $reader;
    }

    private static function oleExtractWorkbook(string $raw): ?string
    {
        $sig = unpack('V', substr($raw, 0, 4))[1];
        if ($sig !== 0xE011CFE0) {
            throw new \RuntimeException('File .xls bukan OLE2 valid. Simpan ulang sebagai .xlsx atau .csv.');
        }
        $sectorShift = unpack('v', substr($raw, 30, 2))[1];
        $sectorSize = 1 << $sectorShift;
        $miniShift = unpack('v', substr($raw, 32, 2))[1];
        $miniSize = 1 << $miniShift;
        $fatSectorCount = unpack('V', substr($raw, 44, 4))[1];
        $dirStart = unpack('V', substr($raw, 48, 4))[1];
        $miniCutoff = unpack('V', substr($raw, 56, 4))[1];
        $miniFatStart = unpack('V', substr($raw, 60, 4))[1];
        $difStart = unpack('V', substr($raw, 68, 4))[1];

        $sectorOffset = fn (int $n): int => 512 + $n * $sectorSize;

        // read FAT
        $fat = [];
        $fatSectors = [];
        for ($i = 0; $i < min($fatSectorCount, 109); $i++) {
            $fatSectors[] = unpack('V', substr($raw, 76 + $i * 4, 4))[1];
        }
        // extra DIFAT sectors when file is large
        $difat = $difStart;
        while ($difat !== 0xFFFFFFFE && $difat < 0xFFFFFFFC && count($fatSectors) < 4096) {
            $difData = substr($raw, $sectorOffset($difat), $sectorSize);
            for ($i = 0; $i < $sectorSize / 4 - 1; $i++) {
                $v = unpack('V', substr($difData, $i * 4, 4))[1];
                if ($v !== 0xFFFFFFFF) {
                    $fatSectors[] = $v;
                }
            }
            $difat = unpack('V', substr($difData, $sectorSize - 4, 4))[1];
        }
        foreach ($fatSectors as $fs) {
            $fatData = substr($raw, $sectorOffset($fs), $sectorSize);
            $count = strlen($fatData) / 4;
            for ($i = 0; $i < $count; $i++) {
                $fat[] = unpack('V', substr($fatData, $i * 4, 4))[1];
            }
        }

        $readChain = function (int $start, int $sizeLimit, bool $mini) use ($raw, $fat, $sectorOffset): string {
            $chunks = [];
            if (! $mini) {
                $s = $start;
                $guard = 0;
                while ($s !== 0xFFFFFFFE && $s < 0xFFFFFFFC && $guard++ < 100000) {
                    $chunks[] = substr($raw, $sectorOffset($s), $sectorSize);
                    $s = $fat[$s] ?? 0xFFFFFFFE;
                }
            } else {
                // mini FAT chain + mini stream (root entry)
                $rootStart = null;
                $s = $start;
                $guard = 0;
                while ($s !== 0xFFFFFFFE && $s < 0xFFFFFFFC && $guard++ < 100000) {
                    $chunks[] = substr($raw, $sectorOffset($s), $sectorSize);
                    $s = $fat[$s] ?? 0xFFFFFFFE;
                }

                return implode('', $chunks);
            }

            return implode('', $chunks);
        };

        // directory entries
        $dirData = $readChain($dirStart, PHP_INT_MAX, false);
        $entries = [];
        $entryCount = intdiv(strlen($dirData), 128);
        for ($i = 0; $i < $entryCount; $i++) {
            $entry = substr($dirData, $i * 128, 128);
            $nameLen = unpack('v', substr($entry, 64, 2))[1];
            if ($nameLen < 2) {
                continue;
            }
            $name = substr($entry, 0, max(0, $nameLen - 2));
            $type = ord($entry[66]);
            $start = unpack('V', substr($entry, 116, 4))[1];
            $size = unpack('V', substr($entry, 120, 4))[1];
            $entries[] = ['name' => $name, 'type' => $type, 'start' => $start, 'size' => $size];
        }

        // root entry holds the mini stream
        $root = null;
        foreach ($entries as $e) {
            if ($e['type'] === 5) {
                $root = $e;
                break;
            }
        }
        $miniStream = $root !== null ? $readChain($root['start'], $root['size'], false) : '';

        $findStream = function (string $needle) use ($entries, $readChain, $miniStream, $miniCutoff): ?string {
            foreach ($entries as $e) {
                if ($e['type'] !== 2) {
                    continue;
                }
                if (strcasecmp($e['name'], $needle) === 0 || str_starts_with($needle, $e['name']) && strlen($e['name']) > 5) {
                    if ($e['size'] < $miniCutoff) {
                        $chunk = substr($miniStream, 0, $e['size']);

                        // walk mini fat — approximation: mini fat is embedded in root's chain
                        return $chunk;
                    }

                    return substr($readChain($e['start'], $e['size'], false), 0, $e['size']);
                }
            }

            return null;
        };

        $workbook = $findStream('Workbook') ?? $findStream('Book');

        return $workbook;
    }

    // ---------- BIFF parsing ----------

    private function parseGlobals(): void
    {
        $pos = 0;
        $len = strlen($this->data);
        while ($pos + 4 <= $len) {
            $rec = unpack('vid', substr($this->data, $pos, 4));
            $id = $rec['d'];
            $size = unpack('v', substr($this->data, $pos + 2, 2))[1];
            $body = substr($this->data, $pos + 4, $size);
            if ($id === self::RECORD_EOF) {
                break;
            }
            if ($id === self::RECORD_SST) {
                $this->parseSst($body);
            } elseif ($id === self::RECORD_BOUNDSHEET) {
                $offset = unpack('V', substr($body, 0, 4))[1];
                $flags = ord($body[4]);
                $nameLen = ord($body[5]);
                $name = $this->readUnicode(substr($body, 6), $nameLen, ord($body[6]) === 0);
                if (($flags & 0x02) === 0) { // visible worksheet
                    $this->sheets[] = ['name' => $name, 'offset' => $offset];
                }
            }
            $pos += 4 + $size;
        }
    }

    private function parseSst(string $body): void
    {
        if (strlen($body) < 8) {
            return;
        }
        $total = unpack('V', substr($body, 0, 4))[1];
        $unique = unpack('V', substr($body, 4, 4))[1];
        $pos = 8;
        for ($i = 0; $i < $unique && $pos + 3 <= strlen($body); $i++) {
            $strLen = unpack('v', substr($body, $pos, 2))[1];
            $flags = ord($body[$pos + 2]);
            $pos += 3;
            $runCount = ($flags & 8) ? unpack('v', substr($body, $pos, 2))[1] : 0;
            $extSize = ($flags & 4) ? unpack('V', substr($body, $pos, 4))[1] : 0;
            if ($flags & 8) {
                $pos += 2;
            }
            if ($flags & 4) {
                $pos += 4;
            }
            $wide = ($flags & 1) === 1;
            $byteLen = $wide ? $strLen * 2 : $strLen;
            $rawStr = substr($body, $pos, $byteLen);
            $pos += $byteLen;
            $this->sst[] = $wide ? mb_convert_encoding($rawStr, 'UTF-8', 'UTF-16LE') : $rawStr;
            $pos += $runCount * 4 + $extSize;
        }
    }

    /** @return array<int,array<int,string|int|float>> rows of cells indexed by column */
    private function parseSheet(int $offset, int $maxRows): array
    {
        $grid = [];
        $pos = $offset;
        $len = strlen($this->data);
        $pendingFormulaString = null;
        while ($pos + 4 <= $len) {
            $id = unpack('v', substr($this->data, $pos, 2))[1];
            $size = unpack('v', substr($this->data, $pos + 2, 2))[1];
            $body = substr($this->data, $pos + 4, $size);
            if ($id === self::RECORD_EOF) {
                break;
            }
            switch ($id) {
                case self::RECORD_LABELSST:
                    $row = unpack('v', substr($body, 0, 2))[1];
                    $col = unpack('v', substr($body, 2, 2))[1];
                    $sst = unpack('V', substr($body, 6, 4))[1];
                    $grid[$row][$col] = $this->sst[$sst] ?? '';
                    $pendingFormulaString = null;
                    break;
                case self::RECORD_LABEL:
                    $row = unpack('v', substr($body, 0, 2))[1];
                    $col = unpack('v', substr($body, 2, 2))[1];
                    $strLen = unpack('v', substr($body, 6, 2))[1];
                    $flags = ord($body[8]);
                    $wide = ($flags & 1) === 1;
                    $rawStr = substr($body, 9, $wide ? $strLen * 2 : $strLen);
                    $grid[$row][$col] = $wide ? mb_convert_encoding($rawStr, 'UTF-8', 'UTF-16LE') : $rawStr;
                    $pendingFormulaString = null;
                    break;
                case self::RECORD_RK:
                    $row = unpack('v', substr($body, 0, 2))[1];
                    $col = unpack('v', substr($body, 2, 2))[1];
                    $rk = unpack('V', substr($body, 6, 4))[1];
                    $grid[$row][$col] = $this->rkValue($rk);
                    $pendingFormulaString = null;
                    break;
                case self::RECORD_MULRK:
                    $row = unpack('v', substr($body, 0, 2))[1];
                    $firstCol = unpack('v', substr($body, 2, 2))[1];
                    $count = intdiv($size - 6, 6);
                    for ($i = 0; $i < $count; $i++) {
                        $rk = unpack('V', substr($body, 4 + $i * 6 + 2, 4))[1];
                        $grid[$row][$firstCol + $i] = $this->rkValue($rk);
                    }
                    $pendingFormulaString = null;
                    break;
                case self::RECORD_NUMBER:
                    $row = unpack('v', substr($body, 0, 2))[1];
                    $col = unpack('v', substr($body, 2, 2))[1];
                    $value = unpack('E', strrev(substr($body, 6, 8)))[1];
                    $grid[$row][$col] = $value;
                    $pendingFormulaString = null;
                    break;
                case self::RECORD_FORMULA:
                    $row = unpack('v', substr($body, 0, 2))[1];
                    $col = unpack('v', substr($body, 2, 2))[1];
                    $valueBytes = substr($body, 6, 8);
                    $value = unpack('E', strrev($valueBytes))[1];
                    if (is_finite($value)) {
                        $grid[$row][$col] = $value;
                    }
                    // string results follow in a STRING record
                    $pendingFormulaString = [$row, $col];
                    break;
                case self::RECORD_STRING:
                    if ($pendingFormulaString !== null) {
                        $strLen = unpack('v', substr($body, 0, 2))[1];
                        $flags = ord($body[2]);
                        $wide = ($flags & 1) === 1;
                        $rawStr = substr($body, 3, $wide ? $strLen * 2 : $strLen);
                        $grid[$pendingFormulaString[0]][$pendingFormulaString[1]] = $wide
                            ? mb_convert_encoding($rawStr, 'UTF-8', 'UTF-16LE')
                            : $rawStr;
                        $pendingFormulaString = null;
                    }
                    break;
                case self::RECORD_DIMENSIONS:
                    if (count($grid) > $maxRows + 100) {
                        return $grid;
                    }
                    break;
            }
            $pos += 4 + $size;
        }

        return $grid;
    }

    private function rkValue(int $rk): float|int
    {
        $isInt = ($rk & 1) === 1;
        $is100 = ($rk & 2) === 2;
        if ($isInt) {
            $value = $rk >> 2;
        } else {
            $bin = pack('V', $rk & 0xFFFFFFFC);
            $value = unpack('E', strrev($bin))[1];
        }
        if ($is100) {
            $value /= 100;
        }

        return $value;
    }

    private function gridToTable(array $grid, int $maxRows): array
    {
        if ($grid === []) {
            return ['headers' => [], 'rows' => []];
        }
        ksort($grid);
        $rowsData = [];
        foreach ($grid as $cells) {
            $rowsData[] = $cells;
        }

        return SpreadsheetReader::normalizeGrid($rowsData, $maxRows);
    }

    private function readUnicode(string $data, int $length, bool $plain): string
    {
        if ($plain) {
            return substr($data, 0, $length);
        }
        // BIFF8 unicode string header: len + flags
        $flags = ord($data[1]);
        $wide = ($flags & 1) === 1;
        $offset = $wide ? 2 : 2;
        $rawStr = substr($data, $offset, $wide ? $length * 2 : $length);

        return $wide ? mb_convert_encoding($rawStr, 'UTF-8', 'UTF-16LE') : $rawStr;
    }
}
