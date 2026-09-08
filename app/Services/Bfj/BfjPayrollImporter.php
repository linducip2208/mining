<?php

namespace App\Services\Bfj;

/**
 * Payroll sheet parser (§31-§43): employee detail sheets are the source,
 * REKAP is control total. ERP recalculates hours via WorkCalendar rules
 * and compares LEGACY vs ERP (§34-§36).
 */
final class BfjPayrollImporter
{
    public const OVERTIME_TYPES = ['LEMBUR_PAGI', 'LEMBUR_SIANG', 'LEMBUR_PERTAMA', 'LEMBUR_SORE', 'LEMBUR_MALAM', 'LEMBUR_HARI_LIBUR'];

    public const SUMMARY_SHEETS = ['REKAP', 'COPY OF REKAP', 'SLIP GAJI'];

    public static function isSummarySheet(string $name): bool
    {
        $u = BfjNormalizer::upper($name);
        foreach (self::SUMMARY_SHEETS as $s) {
            if (str_contains($u, $s)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{normal_start:int|null,normal_end:int|null,normal_hours:float|null,overtime:array<string,float>,issues:array} */
    public static function normalizeDay(array $row, int $shiftStart = 480, int $shiftEnd = 1020, int $breakMin = 60): array
    {
        $issues = [];
        $start = BfjParsers::timeToMinutes((string) ($row['MULAI'] ?? $row['START'] ?? ''));
        $end = BfjParsers::timeToMinutes((string) ($row['SELESAI'] ?? $row['END'] ?? ''));
        $recorded = BfjParsers::parseHours($row['JUMLAH'] ?? $row['NORMAL'] ?? null);
        $normal = null;
        if ($start !== null && $end !== null) {
            $endAdj = $end < $start ? $end + 1440 : $end;
            $normal = max(0, ($endAdj - $start - $breakMin) / 60);
            if ($recorded !== null && abs($normal - $recorded) > 0.26) {
                $issues[] = ['code' => 'HOURS_VARIANCE', 'severity' => 'WARNING', 'message' => "Normal ERP {$normal} vs legacy {$recorded}"];
            }
        }
        $ot = [];
        foreach (['PAGI' => 'LEMBUR_PAGI', 'SIANG' => 'LEMBUR_SIANG', 'PERTAMA' => 'LEMBUR_PERTAMA', 'SORE' => 'LEMBUR_SORE', 'MALAM' => 'LEMBUR_MALAM'] as $key => $type) {
            foreach ($row as $col => $val) {
                if (str_contains(BfjNormalizer::upper((string) $col), $key) && is_numeric($val) || (isset($row[$key]) && is_numeric($row[$key]))) {
                    break;
                }
            }
            $v = BfjParsers::parseHours($row[$key] ?? $row['LEMBUR '.$key] ?? null);
            if ($v !== null && $v > 0) {
                $ot[$type] = $v;
            }
        }

        return ['normal_start' => $start, 'normal_end' => $end, 'normal_hours' => $normal, 'recorded_hours' => $recorded, 'overtime' => $ot, 'issues' => $issues];
    }
}
