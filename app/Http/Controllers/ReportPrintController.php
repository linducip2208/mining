<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\PrintDocumentService;
use App\Support\DateFormatter;
use App\Support\HumanLabel;
use App\Support\NumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class ReportPrintController extends Controller
{
    private const REPORTS = [
        'mining' => [ReportController::class, 'mining', 'Laporan Operasi Tambang'],
        'production' => [ReportController::class, 'production', 'Laporan Produksi'],
        'inventory' => [ReportController::class, 'inventory', 'Laporan Inventory'],
        'sales' => [ReportController::class, 'sales', 'Laporan Penjualan'],
        'hr' => [ReportController::class, 'hr', 'Laporan SDM'],
        'maintenance' => [ReportController::class, 'maintenance', 'Laporan Maintenance'],
        'fleet' => [ReportController::class, 'fleet', 'Laporan Armada'],
        'fuel' => [ReportController::class, 'fuel', 'Laporan BBM'],
        'tire' => [ReportController::class, 'tire', 'Laporan Ban'],
        'dispatch' => [ReportController::class, 'dispatch', 'Laporan Dispatch'],
        'stockpile' => [ReportController::class, 'stockpile', 'Laporan Stockpile'],
        'quality' => [ReportController::class, 'quality', 'Laporan Quality Control'],
        'contract' => [ReportController::class, 'contract', 'Laporan Kontrak'],
        'budget' => [ReportController::class, 'budget', 'Laporan Anggaran'],
        'cash-flow' => [ReportController::class, 'cashFlow', 'Laporan Arus Kas'],
        'finance-trial-balance' => [FinanceReportController::class, 'trialBalance', 'Neraca Saldo'],
        'finance-pl' => [FinanceReportController::class, 'profitLoss', 'Laporan Laba Rugi'],
        'finance-balance-sheet' => [FinanceReportController::class, 'balanceSheet', 'Neraca'],
        'finance-cash-flow' => [ReportController::class, 'cashFlow', 'Laporan Arus Kas'],
    ];

    public function print(Request $request, string $report)
    {
        return $this->response($request, $report, false);
    }

    public function pdf(Request $request, string $report)
    {
        return $this->response($request, $report, true);
    }

    private function response(Request $request, string $report, bool $pdf)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        [$controller, $method, $title] = self::REPORTS[$report];
        $source = app($controller)->{$method}($request);
        abort_unless($source instanceof View, 422, 'Laporan tidak menghasilkan data yang dapat dicetak.');

        $data = $source->getData();
        $rows = $this->rows($report, $data);
        AuditService::log($pdf ? 'PDF_DOWNLOAD' : 'PRINT', 'REPORT', null, null, null, ['report' => $report, 'filters' => $request->query()]);
        $filters = array_filter([
            'Periode' => ($data['from'] ?? $request->from) && ($data['to'] ?? $request->to) ? DateFormatter::short($data['from'] ?? $request->from).' - '.DateFormatter::short($data['to'] ?? $request->to) : null,
            'Bulan' => $data['month'] ?? null,
            'Tahun' => $data['year'] ?? null,
            'Site' => $request->site_id ? '#'.$request->site_id : 'Semua Site',
        ]);
        $payload = ['documentTitle' => $title, 'reportTitle' => $title, 'report' => $report, 'filters' => $filters, 'rows' => $rows, 'headers' => $this->headers($report), 'summary' => $this->summary($report, $data)];
        if ($pdf) {
            return PrintDocumentService::pdf('print.report', $payload, $title.'-'.now()->format('Y-m'));
        }

        return view('print.report', PrintDocumentService::context($payload));
    }

    private function headers(string $report): array
    {
        return match ($report) {
            'production' => ['Batch', 'Tanggal', 'Crusher', 'Input (Ton)', 'Gross (Ton)', 'Loss (Ton)', 'Net (Ton)'],
            'mining' => ['Site', 'Trip', 'Tonase (Ton)', 'Jam Kerja'],
            'sales' => ['Customer', 'Invoice', 'Total', 'Dibayar', 'Outstanding'],
            'fleet' => ['Unit', 'Status', 'Operasi (Jam)', 'Idle (Jam)', 'Downtime (Jam)', 'PA %', 'Utilisasi %'],
            'fuel' => ['Tanggal', 'Nomor', 'Unit', 'Liter', 'Biaya', 'Liter/Jam', 'Variansi'],
            'inventory' => ['Item', 'Gudang', 'Saldo', 'Nilai Persediaan', 'Minimum'],
            'maintenance' => ['Tanggal', 'Work Order', 'Unit', 'Status', 'Biaya Aktual', 'Downtime (Jam)'],
            'dispatch' => ['Trip', 'Tanggal', 'Truk', 'Rute', 'Tonase', 'Status'],
            'stockpile' => ['Stockpile', 'Tanggal Survei', 'Saldo Sistem', 'Saldo Survei', 'Variansi', 'Variansi %'],
            'quality' => ['Sampel', 'Tanggal', 'Material', 'Status'],
            'tire' => ['Serial', 'Merek/Ukuran', 'Status', 'Unit', 'Total Biaya', 'Lifetime HM'],
            'budget' => ['Anggaran', 'Tahun', 'Status', 'Nilai'],
            'finance-trial-balance' => ['Kode Akun', 'Nama Akun', 'Tipe', 'Debit', 'Kredit'],
            'finance-pl' => ['Kode Akun', 'Nama Akun', 'Saldo'],
            'finance-balance-sheet' => ['Kode Akun', 'Nama Akun', 'Saldo'],
            'finance-cash-flow' => ['Tanggal', 'Keterangan', 'Masuk', 'Keluar'],
            default => ['Keterangan', 'Nilai'],
        };
    }

    private function rows(string $report, array $data): array
    {
        $decimal = fn ($v) => NumberFormatter::decimal($v);

        return match ($report) {
            'production' => collect($data['batches'] ?? [])->map(fn ($b) => [$b->number, DateFormatter::short($b->date), $b->crusher?->name, $decimal($b->input_tonnage), $decimal($b->gross_output), $decimal($b->total_loss), $decimal($b->net_output)])->all(),
            'mining' => collect($data['perSite'] ?? [])->map(fn ($r) => [$r->name, $r->trips, $decimal($r->tonnage), $decimal($r->hours)])->all(),
            'sales' => collect($data['perCustomer'] ?? [])->map(fn ($r) => [$r->name, $r->invoices, NumberFormatter::money($r->total), NumberFormatter::money($r->paid), NumberFormatter::money($r->total - $r->paid)])->all(),
            'fleet' => collect($data['rows'] ?? [])->map(fn ($r) => [$r['unit']->code ?? '—', HumanLabel::label($r['unit']->status ?? ''), $decimal($r['kpi']['operating_hours'] ?? 0), $decimal($r['kpi']['idle_hours'] ?? 0), $decimal($r['kpi']['downtime_hours'] ?? 0), $decimal($r['kpi']['pa_pct'] ?? 0), $decimal($r['kpi']['utilization_pct'] ?? 0)])->all(),
            'fuel' => collect($data['issues'] ?? [])->map(fn ($r) => [DateFormatter::short($r->issue_date), $r->number, $r->equipment?->code ?? $r->vehicle_plate, $decimal($r->liter), NumberFormatter::money($r->total_cost), $decimal($r->liter_per_hour), HumanLabel::label($r->variance_status)])->all(),
            'inventory' => collect($data['rows'] ?? [])->map(fn ($r) => [$r->name, $r->warehouse, $decimal($r->balance), NumberFormatter::money($r->cost_value), $decimal($r->min_stock)])->all(),
            'maintenance' => collect($data['workOrders'] ?? [])->map(fn ($r) => [DateFormatter::short($r->date), $r->number, $r->equipment?->name ?? $r->asset?->name, HumanLabel::label($r->status), NumberFormatter::money($r->actual_cost), $decimal($r->downtime_hours)])->all(),
            'dispatch' => collect($data['trips'] ?? [])->map(fn ($r) => [$r->number, DateFormatter::short($r->trip_date), $r->truck?->code, $r->route?->name, $decimal($r->tonnage), HumanLabel::label($r->status)])->all(),
            'stockpile' => collect($data['surveys'] ?? [])->map(fn ($r) => [$r->stockpile?->code, DateFormatter::short($r->survey_date), $decimal($r->system_balance), $decimal($r->survey_balance), $decimal($r->variance), $decimal($r->variance_pct)])->all(),
            'quality' => collect($data['samples'] ?? [])->map(fn ($r) => [$r->number, DateFormatter::short($r->sample_date), $r->item?->name, HumanLabel::label($r->status)])->all(),
            'tire' => collect($data['rows'] ?? [])->map(fn ($r) => [$r['tire']->serial_no, trim($r['tire']->brand.' '.$r['tire']->size), HumanLabel::label($r['tire']->status), $r['tire']->equipment?->code, NumberFormatter::money($r['total_cost']), $decimal($r['life_hm'])])->all(),
            'finance-trial-balance' => collect($data['rows'] ?? [])->map(fn ($r) => [$r->code, $r->name, HumanLabel::label($r->type), NumberFormatter::money($r->debit), NumberFormatter::money($r->credit)])->all(),
            'finance-pl' => collect($data['revenues'] ?? [])->merge($data['expenses'] ?? [])->map(fn ($r) => [$r->code, $r->name, NumberFormatter::money($r->balance)])->all(),
            'finance-balance-sheet' => collect($data['assets'] ?? [])->merge($data['liabilities'] ?? [])->merge($data['equity'] ?? [])->map(fn ($r) => [$r->code, $r->name, NumberFormatter::money($r->balance)])->all(),
            'finance-cash-flow' => collect($data['inflows'] ?? [])->map(fn ($r) => [DateFormatter::short($r->date ?? null), $r->description ?? $r->memo ?? 'Arus Kas', NumberFormatter::money($r->amount ?? 0), '—'])->all(),
            default => $this->genericRows($data),
        };
    }

    private function genericRows(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof Collection && $value->isNotEmpty()) {
                return $value->map(fn ($row) => is_object($row) ? [HumanLabel::label($key), (string) ($row->name ?? $row->number ?? $row->status ?? '—')] : [HumanLabel::label($key), (string) $row])->all();
            }
        }

        return [];
    }

    private function summary(string $report, array $data): array
    {
        return match ($report) {
            'production' => ['Total Batch' => count($data['batches'] ?? []), 'Input' => NumberFormatter::decimal(collect($data['batches'] ?? [])->sum('input_tonnage')).' ton', 'Output Bersih' => NumberFormatter::decimal(collect($data['batches'] ?? [])->sum('net_output')).' ton'],
            'sales' => ['Total Customer' => count($data['perCustomer'] ?? []), 'Nilai Penjualan' => NumberFormatter::money(collect($data['perCustomer'] ?? [])->sum('total')), 'Outstanding' => NumberFormatter::money(collect($data['outstanding'] ?? [])->sum(fn ($i) => $i->total - $i->paid_amount))],
            default => [],
        };
    }
}
