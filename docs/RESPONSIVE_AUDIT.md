# Responsive Audit — Mining ERP

End-to-end responsive UI/UX audit dengan automated viewport testing (Playwright)
dan screenshot evidence. Target: `PARTIAL = 0`, `FAIL = 0` untuk critical pages.

- Runner: `tests/playwright/responsive-audit.mjs`
- Evidence: `docs/responsive-audit/<viewport>/<page>.png` + `audit-results.json`
- Cara jalan: `APP_URL=http://127.0.0.1:8000 node tests/playwright/responsive-audit.mjs [--shots]`
- Hasil terakhir: **136/136 PASS, 0 FAIL** (generated 2026-09-07, lihat `audit-results.json`)

## Viewport

| ID | Ukuran | Grup screenshot |
|---|---|---|
| mobile-360 | 360 x 800 | mobile-360 |
| mobile-390 | 390 x 844 | mobile-390 |
| mobile-430 | 430 x 932 | mobile-390 |
| tablet-768 | 768 x 1024 | tablet-768 |
| tablet-1024 | 1024 x 768 | tablet-1024 |
| laptop-1366 | 1366 x 768 | laptop-1366 |
| desktop-1440 | 1440 x 900 | desktop-1440 |
| desktop-1920 | 1920 x 1080 | desktop-1920 |

## Matrix (PASS / PARTIAL / FAIL)

Urutan kolom: 360 · 390 · 430 · 768 · 1024 · 1366 · 1440 · 1920.

| Page | 360 | 390 | 430 | 768 | 1024 | 1366 | 1440 | 1920 |
|---|---|---|---|---|---|---|---|---|
| login | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| dashboard | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| users | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| roles | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| role-matrix | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| settings | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| printer-devices | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| approval-center | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| weighbridge | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| production | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| fuel | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| inventory | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| invoices | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| finance-report | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| user-create | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| weighbridge-create | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| production-create | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |

## Yang diuji otomatis per viewport

- `documentElement.scrollWidth > clientWidth` = FAIL (page overflow),
  termasuk identifikasi elemen pelanggar.
- Elemen lebih lebar dari viewport (di luar scroll container).
- Canvas Chart.js keluar viewport.
- Input/select/textarea di luar viewport.
- Mobile (<768px): geometri drawer sidebar + overlay, dialog modal
  (lebar viewport, terpotong, body scrollable).

## Perbaikan yang dilakukan (reusable components dulu)

- `layouts/partials/sidebar`: drawer mobile (max 85vw), overlay, tombol
  close, tutup saat route dipilih, ESC, focus management.
- `layouts/app` topbar: burger 44px, search mobile via command palette,
  dropdown notifikasi/user dibatasi `max-w: calc(100vw - 1.5rem)`.
- `x-ui.modal` / `x-ui.drawer`: bottom sheet di mobile, near-full width,
  body scroll internal, ESC.
- `x-ui.page-header`: aksi wrap ke baris sendiri di mobile.
- `x-ui.filter-bar` + dashboard filter: toggle Filter collapsible di mobile,
  field full-width.
- `x-ui.pagination`: ringkas di mobile (Previous · halaman · Next).
- Dashboard KPI: grid 2 kolom di mobile, value wrap, label truncate.
- `x-ui.table` / `x-table`: scroll container internal (strategi B/C §10).
- `roles/show`: permission matrix `min-width: 780px` + checkbox 20px
  (controlled scroll, tidak di-squeeze).
- `.form-actions-sticky`: tanpa negative margin (sebelumnya overflow 16px
  di mobile), sticky bottom bar.
- `whitespace-nowrap` untuk nomor dokumen/tiket/faktur, tanggal, nopol,
  dan nominal agar tidak terpotong di tengah token.
- Safety net global: `.page-frame` / `#mainContent` `overflow-x: clip`.

## Keputusan yang disengaja

- Pola header `flex … justify-between` di ~88 index views TIDAK diubah
  massal: hasil render aktual 360px terbukti tidak overflow (judul wrap,
  satu tombol aksi muat). Mengubah 88 file = copy-paste hack (§41).
- Print templates (`views/print/**`, fixed 58/80mm/A4) TIDAK dibuat
  responsive — output cetak bukan viewport (§20, §50).
- Tidak ada bottom nav; drawer tetap navigasi utama (§40).
- Tidak ada duplikasi DOM berat untuk varian mobile (§47).

## Validasi

- `php artisan optimize:clear` — OK.
- `php artisan route:list --except-vendor` — 631 baris, OK.
- `npm run build` — OK.
- Playwright responsive suite — 136/136 PASS.
- Suite terkait area: SidebarRouteTest 4/4, PrintDeviceManagerTest 5/5,
  PrintDocumentTest 3/3, SettingsUiTest 1/1, ApprovalEngineTest 5/5,
  NewModulesSmokeTest 3/3, AdminUiTest 4/4 — PASS.
- Full `php artisan test`: ada failure PRE-EXISTING yang tidak terkait
  perubahan ini (`no such table: settings` di sqlite :memory: saat
  me-render halaman error 403/404 via `BrandingService` — file
  `errors/*`, `BrandingService`, migrasi, dan TestCase tidak tersentuh
  diff ini).
