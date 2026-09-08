<?php

namespace App\Services\Bfj;

/**
 * Minimal multi-sheet .xlsx writer (inline strings, no styling) for the
 * BFJ acceptance summary (§87). No external dependency; aggregate values
 * only, never sensitive detail.
 */
final class BfjExcelWriter
{
    /** @param  array<int, array{0:string,1:string[],2:array<int,array<int,mixed>>}>  $sheets */
    public static function write(string $path, array $sheets): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot write '.$path);
        }
        $zip->addFromString('[Content_Types].xml', self::contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', self::rels());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($sheets)));
        foreach ($sheets as $i => [$name, $headers, $rows]) {
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', self::sheet($headers, $rows));
        }
        $zip->close();
    }

    private static function esc(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function contentTypes(int $n): string
    {
        $s = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>';
        $s .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $s .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $s.'</Types>';
    }

    private static function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private static function workbook(array $sheets): string
    {
        $s = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($sheets as $i => [$name]) {
            $s .= '<sheet name="'.self::esc(mb_substr($name, 0, 31)).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>';
        }

        return $s.'</sheets></workbook>';
    }

    private static function workbookRels(int $n): string
    {
        $s = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $n; $i++) {
            $s .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        return $s.'</Relationships>';
    }

    private static function sheet(array $headers, array $rows): string
    {
        $s = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $all = array_merge([$headers], $rows);
        foreach ($all as $ri => $line) {
            $s .= '<row r="'.($ri + 1).'">';
            foreach (array_values($line) as $ci => $v) {
                $ref = SpreadsheetCol::name($ci).($ri + 1);
                if (is_numeric($v) && $v !== '') {
                    $s .= '<c r="'.$ref.'"><v>'.$v.'</v></c>';
                } else {
                    $s .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.self::esc($v).'</t></is></c>';
                }
            }
            $s .= '</row>';
        }

        return $s.'</sheetData></worksheet>';
    }
}

final class SpreadsheetCol
{
    public static function name(int $index): string
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
}
