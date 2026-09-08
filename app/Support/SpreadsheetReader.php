<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Native spreadsheet reader for the legacy import wizard.
 *
 * Supports .xlsx (Office Open XML via ZipArchive + SimpleXML), .xls (BIFF8
 * binary via the OLE compound-document reader below) and .csv — without any
 * external package. Macro-enabled .xlsm and OLE .xls with VBA storage are
 * rejected because they can carry executable payloads.
 *
 * Values are returned as strings exactly like fgetcsv would produce, so the
 * existing ImportService mapping/validation pipeline works unchanged.
 */
final class SpreadsheetReader
{
    public const SUPPORTED = ['csv', 'txt', 'xlsx', 'xls'];

    /**
     * Read the first (or named) sheet. Returns ['headers' => [], 'rows' => [[...]]].
     */
    public static function read(string $path, ?string $sheetName = null, int $maxRows = 5000): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'csv', 'txt' => self::readCsv($path, $maxRows),
            'xlsx' => self::readXlsx($path, $sheetName, $maxRows),
            'xls' => self::readXls($path, $sheetName, $maxRows),
            default => throw new \RuntimeException("Format file .{$ext} tidak didukung. Gunakan CSV, XLSX, atau XLS."),
        };
    }

    /**
     * @return array<int, string> sheet names
     */
    public static function sheets(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['csv', 'txt'], true)) {
            return ['Sheet1'];
        }
        if ($ext === 'xlsx') {
            return self::xlsxSheets($path);
        }
        if ($ext === 'xls') {
            return self::xlsSheets($path);
        }

        throw new \RuntimeException("Format file .{$ext} tidak didukung.");
    }

    /**
     * Reject risky formats we cannot import safely.
     */
    public static function assertSafe(string $originalName, string $clientMime): void
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsm', 'xlsb', 'xla', 'xlam'], true)) {
            throw new \RuntimeException("Format .{$ext} (macro-enabled/binary) tidak diizinkan karena berisiko. Simpan ulang sebagai .xlsx atau .csv.");
        }
        if (preg_match('/(php|phtml|phar|exe|sh|bat|js|html?)$/i', $ext)) {
            throw new \RuntimeException('Tipe file tidak diizinkan.');
        }
        $allowedMimes = [
            'text/csv', 'text/plain', 'application/csv', 'text/x-csv',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet',
        ];
        if ($clientMime !== '' && ! in_array($clientMime, $allowedMimes, true)) {
            throw new \RuntimeException("MIME type '{$clientMime}' tidak diizinkan.");
        }
    }

    // ================= CSV =================

    private static function readCsv(string $path, int $maxRows): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException('File tidak dapat dibaca.');
        }
        $headers = null;
        $rows = [];
        while (($line = fgetcsv($handle)) !== false && count($rows) < $maxRows) {
            if ($headers === null) {
                $headers = array_map(fn ($h) => trim((string) $h), $line);

                continue;
            }
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
        }
        fclose($handle);

        return ['headers' => $headers ?? [], 'rows' => $rows];
    }

    // ================= XLSX =================

    private static function xlsxSheets(string $path): array
    {
        $zip = self::openZip($path);
        $names = [];
        $entry = $zip->locateName('xl/workbook.xml');
        if ($entry !== false) {
            $xml = simplexml_load_string((string) $zip->getFromIndex($entry));
            if ($xml !== false) {
                foreach ($xml->sheets->sheet as $sheet) {
                    $names[] = (string) $sheet['name'];
                }
            }
        }
        $zip->close();

        return $names;
    }

    private static function readXlsx(string $path, ?string $sheetName, int $maxRows): array
    {
        $zip = self::openZip($path);

        // shared strings
        $shared = [];
        $entry = $zip->locateName('xl/sharedStrings.xml');
        if ($entry !== false) {
            $xml = simplexml_load_string((string) $zip->getFromIndex($entry));
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    $text = '';
                    // rich-text runs + plain value
                    if (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    } else {
                        $text = (string) $si->t;
                    }
                    $shared[] = $text;
                }
            }
        }

        // resolve requested sheet (workbook order ↔ sheetN.xml via rels)
        $sheets = self::xlsxSheetMap($zip);
        $target = null;
        foreach ($sheets as $name => $file) {
            if ($sheetName === null || $name === $sheetName) {
                $target = $file;
                if ($sheetName !== null) {
                    break;
                }
            }
        }
        if ($target === null && $sheetName !== null) {
            // fall back to workbook order index
            $all = array_values($sheets);
            $index = array_search($sheetName, array_keys($sheets), true);
            if ($index !== false && isset($all[$index])) {
                $target = $all[$index];
            }
        }
        if ($target === null) {
            $zip->close();
            throw new \RuntimeException('Sheet tidak ditemukan.');
        }
        $entry = $zip->locateName($target);
        if ($entry === false) {
            $zip->close();
            throw new \RuntimeException('Isi sheet tidak dapat dibaca.');
        }
        $xml = simplexml_load_string((string) $zip->getFromIndex($entry));
        $zip->close();
        if ($xml === false) {
            throw new \RuntimeException('File XLSX rusak atau bukan Office Open XML valid.');
        }

        $rowsData = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            $col = 0;
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $colIndex = $ref !== '' ? self::columnIndex($ref) : $col;
                $type = (string) $c['t'];
                $value = '';
                if ($type === 's') {
                    $idx = (int) $c->v;
                    $value = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $c->is->t;
                } elseif ($type === 'b') {
                    $value = ((int) $c->v) ? 'TRUE' : 'FALSE';
                } else {
                    $raw = (string) $c->v;
                    // dates stored as serial numbers with a date style
                    if ($raw !== '' && isset($c['s']) && self::isDateStyle((string) $c['s'])) {
                        $serial = (float) $raw;
                        if ($serial > 0 && $serial < 60000) {
                            $value = self::excelSerialToDate($serial)->format('Y-m-d');
                        } else {
                            $value = $raw;
                        }
                    } else {
                        $value = $raw;
                    }
                }
                $cells[$colIndex] = $value;
                $col = $colIndex + 1;
            }
            $rowsData[] = $cells;
            if (count($rowsData) > $maxRows + 50) {
                break;
            }
        }

        return self::normalizeGrid($rowsData, $maxRows);
    }

    /**
     * @return array<string,string> sheet name → zip entry path
     */
    private static function xlsxSheetMap(\ZipArchive $zip): array
    {
        $map = [];
        $entry = $zip->locateName('xl/workbook.xml');
        if ($entry === false) {
            return $map;
        }
        $xml = simplexml_load_string((string) $zip->getFromIndex($entry));
        if ($xml === false) {
            return $map;
        }
        // rels: rId → target file
        $rels = [];
        $relEntry = $zip->locateName('xl/_rels/workbook.xml.rels');
        if ($relEntry !== false) {
            $relXml = simplexml_load_string((string) $zip->getFromIndex($relEntry));
            if ($relXml !== false) {
                foreach ($relXml->Relationship as $rel) {
                    $rels[(string) $rel['Id']] = 'xl/'.ltrim((string) $rel['Target'], '/');
                }
            }
        }
        foreach ($xml->sheets->sheet as $sheet) {
            $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
            $name = (string) $sheet['name'];
            if (isset($rels[$rid])) {
                $map[$name] = $rels[$rid];
            } else {
                $map[$name] = 'xl/worksheets/sheet'.((int) $sheet['sheetId']).'.xml';
            }
        }

        return $map;
    }

    /** cell styles whose numFmt is a date format */
    private static function isDateStyle(string $styleIndex): bool
    {
        // common built-in date style indexes in Excel default styles
        $builtIn = [14, 15, 16, 17, 18, 19, 20, 21, 22, 27, 30, 36, 45, 46, 47];

        return in_array((int) $styleIndex, $builtIn, true);
    }

    private static function columnIndex(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $n = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $n - 1);
    }

    /**
     * 0-based column index → spreadsheet letters (A, B, ..., AA).
     */
    public static function columnName(int $index): string
    {
        $name = '';
        $index += 1;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod).$name;
            $index = intdiv($index - $mod, 26);
        }

        return $name;
    }

    public static function normalizeGrid(array $grid, int $maxRows): array
    {
        $width = 0;
        foreach ($grid as $cells) {
            $keys = array_keys($cells);
            if ($keys !== [] && max($keys) + 1 > $width) {
                $width = max($keys) + 1;
            }
        }
        if ($width === 0) {
            return ['headers' => [], 'rows' => []];
        }
        $headers = null;
        $rows = [];
        foreach ($grid as $cells) {
            $line = [];
            for ($i = 0; $i < $width; $i++) {
                $line[] = trim((string) ($cells[$i] ?? ''));
            }
            if ($headers === null) {
                if (count(array_filter($line, fn ($v) => $v !== '')) === 0) {
                    continue;
                }
                $headers = $line;

                continue;
            }
            if (count(array_filter($line, fn ($v) => $v !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
            if (count($rows) >= $maxRows) {
                break;
            }
        }

        return ['headers' => $headers ?? [], 'rows' => $rows];
    }

    private static function openZip(string $path): \ZipArchive
    {
        $zip = new \ZipArchive;
        $result = $zip->open($path, \ZipArchive::RDONLY);
        if ($result !== true) {
            throw new \RuntimeException('File XLSX tidak dapat dibuka (rusak atau bukan file zip valid).');
        }
        // macro-enabled workbooks contain a vbaProject — refuse them
        if ($zip->locateName('xl/vbaProject.bin') !== false) {
            $zip->close();
            throw new \RuntimeException('File berisi macro (vbaProject.bin) — tidak diizinkan. Simpan ulang tanpa macro.');
        }

        return $zip;
    }

    // ================= XLS (BIFF 8) =================

    private static function xlsSheets(string $path): array
    {
        try {
            return XlsBiffReader::sheetNames($path);
        } catch (\Throwable) {
            return ['Sheet1'];
        }
    }

    private static function readXls(string $path, ?string $sheetName, int $maxRows): array
    {
        return XlsBiffReader::read($path, $sheetName, $maxRows);
    }

    // ================= shared date helper =================

    public static function excelSerialToDate(float $serial): Carbon
    {
        // Excel epoch 1900-01-01 with the leap-year bug (serial 60 = 1900-02-29 fake)
        $unix = ($serial - 25569) * 86400;

        return Carbon::createFromTimestampUTC((int) round($unix));
    }
}
