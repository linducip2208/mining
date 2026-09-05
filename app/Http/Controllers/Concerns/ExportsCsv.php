<?php

namespace App\Http\Controllers\Concerns;

trait ExportsCsv
{
    protected function exportCsv(string $name, array $headers, iterable $rows)
    {
        $filename = $name . '-' . now()->format('YmdHis') . '.csv';
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
