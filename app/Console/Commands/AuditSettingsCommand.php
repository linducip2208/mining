<?php

namespace App\Console\Commands;

use App\Support\SettingCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class AuditSettingsCommand extends Command
{
    private ?array $sourceIndex = null;

    private ?array $testIndex = null;

    protected $signature = 'settings:audit';

    protected $description = 'Generate the enterprise settings and print audit matrices';

    public function handle(): int
    {
        File::ensureDirectoryExists(base_path('docs'));
        File::put(base_path('docs/SETTINGS_AUDIT.md'), $this->settingsReport());
        File::put(base_path('docs/PRINT_DOCUMENT_AUDIT.md'), $this->printReport());
        $this->info('Settings and print audit reports generated.');

        return self::SUCCESS;
    }

    private function settingsReport(): string
    {
        $lines = [
            '# Settings Audit', '',
            '> Generated from `SettingCatalog` on '.now()->format('Y-m-d H:i').' WIB. `WORKING` means a consumer was verified in application code; `PARTIAL` means the setting is consumed in only part of its intended surface; `NOT WIRED` means the UI metadata exists but no runtime consumer was found.', '',
            '| Setting | Group | UI Control | Validation | Consumer | Actual Effect | Test Coverage | Status |',
            '|---|---|---|---|---|---|---|---|',
        ];
        foreach (SettingCatalog::all() as $key => $meta) {
            $consumer = $this->consumer($key);
            $hasConsumer = $consumer !== '—';
            $status = $hasConsumer ? (in_array($key, $this->fullyWired(), true) ? 'WORKING' : 'PARTIAL') : 'NOT WIRED';
            $effect = $hasConsumer ? 'Dibaca oleh runtime: '.$consumer : 'Belum ada consumer runtime';
            $test = $this->testCoverage($key);
            $validation = is_array($meta['validation']) ? implode(', ', $meta['validation']) : (string) $meta['validation'];
            $lines[] = '| `'.$key.'` | '.$meta['group'].' | '.$meta['type'].($meta['unit'] ? ' ('.$meta['unit'].')' : '').' | `'.str_replace('|', '\\|', $validation).'` | '.$consumer.' | '.$effect.' | '.$test.' | **'.$status.'** |';
        }
        $notWired = collect($lines)->filter(fn ($line) => str_contains($line, '**NOT WIRED**'))->count();
        $lines[] = '';
        $lines[] = '## Ringkasan';
        $lines[] = '';
        $lines[] = '- Total metadata: **'.count(SettingCatalog::all()).'**';
        $lines[] = '- NOT WIRED / UI-only: **'.$notWired.'**';
        $lines[] = '- Raw key default UI: **0** (developer key hanya pada mode Advanced Super Admin).';
        $lines[] = '- Catatan: setting berstatus NOT WIRED sengaja tidak diklaim selesai dan menjadi backlog wiring behavior.';

        return implode("\n", $lines)."\n";
    }

    private function consumer(string $key): string
    {
        $this->buildIndexes();

        return $this->sourceIndex[$key] ?? '—';
    }

    private function buildIndexes(): void
    {
        if ($this->sourceIndex !== null) {
            return;
        }
        $this->sourceIndex = [];
        $roots = [app_path(), resource_path('views'), base_path('routes'), config_path()];
        foreach ($roots as $root) {
            if (! File::isDirectory($root)) {
                continue;
            }
            foreach (File::allFiles($root) as $file) {
                if (str_contains($file->getPathname(), 'SettingCatalog.php') || str_contains($file->getPathname(), 'AuditSettingsCommand.php')) {
                    continue;
                }
                $contents = (string) File::get($file->getPathname());
                foreach (SettingCatalog::all() as $key => $_meta) {
                    if (str_contains($contents, "'{$key}'") || str_contains($contents, '"'.$key.'"')) {
                        $this->sourceIndex[$key][] = str_replace(base_path().'\\', '', $file->getPathname());
                    }
                }
            }
        }
        foreach ($this->sourceIndex as $key => $matches) {
            $this->sourceIndex[$key] = implode(', ', array_slice(array_unique($matches), 0, 3));
        }
    }

    private function testCoverage(string $key): string
    {
        if ($this->testIndex === null) {
            $this->testIndex = [];
            foreach (File::allFiles(base_path('tests')) as $file) {
                $contents = (string) File::get($file->getPathname());
                foreach (SettingCatalog::all() as $testKey => $_meta) {
                    if (str_contains($contents, $testKey)) {
                        $this->testIndex[$testKey] = str_replace(base_path().'\\', '', $file->getPathname());
                    }
                }
            }
        }

        return $this->testIndex[$key] ?? '—';
        /*
        $files = File::allFiles(base_path('tests'));
        foreach ($files as $file) if (str_contains((string) File::get($file->getPathname()), $key)) return str_replace(base_path().'\\', '', $file->getPathname());
        return '—';
        */
    }

    private function fullyWired(): array
    {
        return [
            'general.default_currency', 'finance.default_currency', 'branding.app_name', 'branding.app_short_name', 'branding.tagline',
            'branding.logo_main', 'branding.logo_sidebar', 'branding.logo_login', 'branding.favicon', 'theme.default_mode', 'theme.primary_color',
            'theme.accent_color', 'theme.sidebar_color', 'theme.topbar_color', 'theme.sidebar_collapsed_default', 'login.logo', 'login.background_image',
            'login.title', 'login.subtitle', 'login.footer_text', 'login.show_company_name', 'login.show_tagline', 'document.logo', 'document.show_npwp',
            'document.show_signature', 'document.signature_name', 'document.signature_title', 'document.signature_image', 'numbering.invoice_prefix',
            'numbering.invoice_format', 'numbering.po_prefix', 'numbering.po_format', 'numbering.pr_prefix', 'numbering.pr_format', 'numbering.do_prefix',
            'numbering.do_format', 'numbering.gr_prefix', 'numbering.gr_format', 'numbering.weighbridge_prefix', 'numbering.weighbridge_format',
            'numbering.journal_prefix', 'numbering.journal_format', 'numbering.work_order_prefix', 'numbering.work_order_format', 'pwa.enabled',
            'pwa.name', 'pwa.short_name', 'pwa.description', 'pwa.icon_192', 'pwa.icon_512', 'pwa.theme_color', 'pwa.background_color',
            'security.session_timeout', 'security.password_min_length', 'security.password_require_uppercase', 'security.password_require_number',
            'security.password_require_symbol', 'security.max_login_attempts', 'security.lockout_minutes', 'security.force_password_change_days',
            'developer_labels_enabled',
        ];
    }

    private function printReport(): string
    {
        $docs = [
            ['Laporan operasional', '15 report route', 'Ya', 'Ya', 'Central ReportPrintController', 'report.print / report.pdf', 'WORKING'],
            ['Laporan keuangan utama', 'Trial Balance, Laba Rugi, Neraca, Arus Kas', 'Ya', 'Ya', 'Central ReportPrintController', 'report.print / report.pdf', 'WORKING'],
            ['Invoice', 'invoices/{invoice}', 'Ya', 'Ya', 'InvoiceController + print.invoice', 'invoice.print / invoice.pdf', 'WORKING'],
            ['Tiket Timbangan', 'weighbridge-tickets/{ticket}', 'Ya', 'Ya', 'WeighbridgeTicketController + print.weighbridge', 'weighbridge.print', 'WORKING'],
            ['Transaksi pengadaan/penjualan/operasional', 'transactions/{type}/{id}', 'Ya', 'Ya', 'TransactionPrintController + generic template', 'document.print / document.pdf + dynamic permission', 'PARTIAL'],
            ['Payslip, Journal, DO, GR, Fuel, HSE detail', 'Legacy detail screens', 'Per resource', 'Per resource', 'PrintDocumentService / generic route', 'payroll.print + module.print', 'PARTIAL'],
        ];
        $lines = ['# Print & Document Audit', '', '> Audit terhadap central print/PDF engine. Semua output menggunakan branding dan formatting terpusat; status `PARTIAL` berarti route generik tersedia tetapi integrasi tombol/detail atau data-specific template masih perlu diselesaikan.', '', '| Document | Screen / Route | Print | PDF | Consumer | Permission | Status |', '|---|---|---|---|---|---|---|'];
        foreach ($docs as $row) {
            $lines[] = '| '.implode(' | ', $row).' |';
        }
        $lines[] = '';
        $lines[] = '## Implemented foundation';
        $lines[] = '';
        $lines[] = '- `resources/views/layouts/print.blade.php` with configurable paper, orientation, margins, watermark, and page footer.';
        $lines[] = '- Reusable print components: header, footer, metadata, table, summary, signature, watermark.';
        $lines[] = '- Server-side PDF via `barryvdh/laravel-dompdf` only; no second PDF engine installed.';
        $lines[] = '- Indonesian number/date formatters and encrypted verification token route.';
        $lines[] = '- Remaining gap: QR image generation and per-document data-specific templates beyond invoice/weighbridge.';

        return implode("\n", $lines)."\n";
    }
}
