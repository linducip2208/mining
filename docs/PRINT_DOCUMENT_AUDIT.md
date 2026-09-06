# Print & Document Audit

> Audit terhadap central print/PDF engine. Semua output menggunakan branding dan formatting terpusat; status `PARTIAL` berarti route generik tersedia tetapi integrasi tombol/detail atau data-specific template masih perlu diselesaikan.

| Document | Screen / Route | Print | PDF | Consumer | Permission | Status |
|---|---|---|---|---|---|---|
| Laporan operasional | 15 report route | Ya | Ya | Central ReportPrintController | report.print / report.pdf | WORKING |
| Laporan keuangan utama | Trial Balance, Laba Rugi, Neraca, Arus Kas | Ya | Ya | Central ReportPrintController | report.print / report.pdf | WORKING |
| Invoice | invoices/{invoice} | Ya | Ya | InvoiceController + print.invoice | invoice.print / invoice.pdf | WORKING |
| Tiket Timbangan | weighbridge-tickets/{ticket} | Ya | Ya | WeighbridgeTicketController + print.weighbridge | weighbridge.print | WORKING |
| Transaksi pengadaan/penjualan/operasional | transactions/{type}/{id} | Ya | Ya | TransactionPrintController + generic template | document.print / document.pdf + dynamic permission | PARTIAL |
| Payslip, Journal, DO, GR, Fuel, HSE detail | Legacy detail screens | Per resource | Per resource | PrintDocumentService / generic route | payroll.print + module.print | PARTIAL |

## Implemented foundation

- `resources/views/layouts/print.blade.php` with configurable paper, orientation, margins, watermark, and page footer.
- Reusable print components: header, footer, metadata, table, summary, signature, watermark.
- Server-side PDF via `barryvdh/laravel-dompdf` only; no second PDF engine installed.
- Indonesian number/date formatters and encrypted verification token route.
- Remaining gap: QR image generation and per-document data-specific templates beyond invoice/weighbridge.
