# Settings Audit

> Generated from `SettingCatalog` on 2026-09-06 08:52 WIB. `WORKING` means a consumer was verified in application code; `PARTIAL` means the setting is consumed in only part of its intended surface; `NOT WIRED` means the UI metadata exists but no runtime consumer was found.

| Setting | Group | UI Control | Validation | Consumer | Actual Effect | Test Coverage | Status |
|---|---|---|---|---|---|---|---|
| `general.timezone` | Umum | select | `nullable\|string\|max:100` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `general.locale` | Umum | select | `nullable\|string\|max:100` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `general.date_format` | Umum | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `general.time_format` | Umum | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `general.number_locale` | Umum | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `general.default_currency` | Umum | select | `nullable\|string\|max:100` | app\Providers\AppServiceProvider.php, app\Support\CurrencyFormatter.php, app\Support\NumberFormatter.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php, app\Support\CurrencyFormatter.php, app\Support\NumberFormatter.php | tests\Unit\SettingSearchTest.php | **WORKING** |
| `finance.default_currency` | Keuangan | select | `nullable\|string\|max:100` | app\Providers\AppServiceProvider.php, app\Support\CurrencyFormatter.php, app\Support\NumberFormatter.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php, app\Support\CurrencyFormatter.php, app\Support\NumberFormatter.php | tests\Unit\SettingLabelTest.php | **WORKING** |
| `general.currency_position` | Umum | select | `nullable\|string\|max:100` | app\Support\CurrencyFormatter.php | Dibaca oleh runtime: app\Support\CurrencyFormatter.php | — | **PARTIAL** |
| `general.decimal_places` | Umum | integer (digit) | `integer\|min:0\|max:4` | app\Support\CurrencyFormatter.php, app\Support\NumberFormatter.php | Dibaca oleh runtime: app\Support\CurrencyFormatter.php, app\Support\NumberFormatter.php | — | **PARTIAL** |
| `system.company_name` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php, resources\views\settings\index.blade.php | Dibaca oleh runtime: app\Services\BrandingService.php, resources\views\settings\index.blade.php | tests\Unit\BrandingLabelTest.php | **PARTIAL** |
| `system.company_legal_name` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_address` | Perusahaan | textarea | `nullable\|string\|max:5000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_city` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_province` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_postal_code` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_phone` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_npwp` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_pic` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_registration_no` | Perusahaan | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_email` | Perusahaan | text | `nullable\|email\|max:255` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `system.company_website` | Perusahaan | text | `nullable\|url\|max:255` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `branding.app_name` | Branding | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php, resources\views\settings\index.blade.php | Dibaca oleh runtime: app\Services\BrandingService.php, resources\views\settings\index.blade.php | tests\Unit\SettingsImportExportTest.php | **WORKING** |
| `branding.app_short_name` | Branding | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php, resources\views\settings\index.blade.php | Dibaca oleh runtime: app\Services\BrandingService.php, resources\views\settings\index.blade.php | — | **WORKING** |
| `branding.tagline` | Branding | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php, resources\views\settings\index.blade.php | Dibaca oleh runtime: app\Services\BrandingService.php, resources\views\settings\index.blade.php | tests\Unit\BrandingLabelTest.php | **WORKING** |
| `branding.copyright_text` | Branding | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `branding.powered_by_text` | Branding | text | `nullable\|string\|max:1000` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `branding.powered_by_url` | Branding | text | `nullable\|url\|max:255` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | tests\Unit\BrandingSettingTest.php | **PARTIAL** |
| `branding.logo_main` | Branding | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | app\Services\PrintDocumentService.php, resources\views\auth\login.blade.php, resources\views\components\print\header.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\auth\login.blade.php, resources\views\components\print\header.blade.php | tests\Unit\BrandingUploadTest.php | **WORKING** |
| `branding.logo_sidebar` | Branding | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | resources\views\layouts\partials\sidebar.blade.php | Dibaca oleh runtime: resources\views\layouts\partials\sidebar.blade.php | — | **WORKING** |
| `branding.logo_sidebar_collapsed` | Branding | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `branding.logo_dark` | Branding | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `branding.logo_login` | Branding | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `branding.favicon` | Branding | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | resources\views\errors\layout.blade.php, resources\views\layouts\app.blade.php, resources\views\layouts\guest.blade.php | Dibaca oleh runtime: resources\views\errors\layout.blade.php, resources\views\layouts\app.blade.php, resources\views\layouts\guest.blade.php | tests\Unit\BrandingSettingTest.php | **WORKING** |
| `branding.primary_color` | Branding | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php, resources\views\settings\index.blade.php | Dibaca oleh runtime: app\Services\BrandingService.php, resources\views\settings\index.blade.php | tests\Unit\BrandingSettingTest.php | **PARTIAL** |
| `branding.accent_color` | Branding | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **PARTIAL** |
| `branding.sidebar_color` | Branding | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php, resources\views\settings\index.blade.php | Dibaca oleh runtime: app\Services\BrandingService.php, resources\views\settings\index.blade.php | — | **PARTIAL** |
| `branding.topbar_color` | Branding | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php, resources\views\settings\index.blade.php | Dibaca oleh runtime: app\Services\BrandingService.php, resources\views\settings\index.blade.php | — | **PARTIAL** |
| `theme.default_mode` | Theme & Layout | select | `nullable\|string\|max:100` | resources\views\layouts\app.blade.php | Dibaca oleh runtime: resources\views\layouts\app.blade.php | — | **WORKING** |
| `theme.primary_color` | Theme & Layout | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **WORKING** |
| `theme.accent_color` | Theme & Layout | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **WORKING** |
| `theme.sidebar_color` | Theme & Layout | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **WORKING** |
| `theme.topbar_color` | Theme & Layout | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Services\BrandingService.php | Dibaca oleh runtime: app\Services\BrandingService.php | — | **WORKING** |
| `theme.sidebar_style` | Theme & Layout | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `theme.sidebar_collapsed_default` | Theme & Layout | boolean | `boolean` | resources\views\layouts\app.blade.php | Dibaca oleh runtime: resources\views\layouts\app.blade.php | — | **WORKING** |
| `theme.density` | Theme & Layout | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `theme.border_radius` | Theme & Layout | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `login.logo` | Login Page | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `login.background_image` | Login Page | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `login.title` | Login Page | text | `nullable\|string\|max:1000` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `login.subtitle` | Login Page | text | `nullable\|string\|max:1000` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `login.footer_text` | Login Page | text | `nullable\|string\|max:1000` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `login.show_company_name` | Login Page | boolean | `boolean` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `login.show_tagline` | Login Page | boolean | `boolean` | resources\views\auth\login.blade.php | Dibaca oleh runtime: resources\views\auth\login.blade.php | — | **WORKING** |
| `document.logo` | Dokumen & Cetak | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | — | **WORKING** |
| `document.header_text` | Dokumen & Cetak | text | `nullable\|string\|max:1000` | app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | — | **PARTIAL** |
| `document.footer_text` | Dokumen & Cetak | text | `nullable\|string\|max:1000` | app\Services\PrintDocumentService.php, resources\views\components\print\footer.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\footer.blade.php | — | **PARTIAL** |
| `document.watermark` | Dokumen & Cetak | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `document.show_npwp` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | — | **WORKING** |
| `document.show_signature` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php, resources\views\components\print\signature.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\signature.blade.php | — | **WORKING** |
| `document.signature_name` | Dokumen & Cetak | text | `nullable\|string\|max:1000` | app\Services\PrintDocumentService.php, resources\views\components\print\signature.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\signature.blade.php | — | **WORKING** |
| `document.signature_title` | Dokumen & Cetak | text | `nullable\|string\|max:1000` | app\Services\PrintDocumentService.php, resources\views\components\print\signature.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\signature.blade.php | — | **WORKING** |
| `document.signature_image` | Dokumen & Cetak | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `document.paper_size` | Dokumen & Cetak | select | `nullable\|string\|max:100` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.orientation` | Dokumen & Cetak | select | `nullable\|string\|max:100` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.margin_top` | Dokumen & Cetak | decimal (mm) | `numeric\|min:0\|max:60` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.margin_right` | Dokumen & Cetak | decimal (mm) | `numeric\|min:0\|max:60` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.margin_bottom` | Dokumen & Cetak | decimal (mm) | `numeric\|min:0\|max:60` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.margin_left` | Dokumen & Cetak | decimal (mm) | `numeric\|min:0\|max:60` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.show_address` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | — | **PARTIAL** |
| `document.show_phone` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | — | **PARTIAL** |
| `document.show_email` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\header.blade.php | — | **PARTIAL** |
| `document.show_qr` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.show_page_number` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php, resources\views\components\print\footer.blade.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php, resources\views\components\print\footer.blade.php | — | **PARTIAL** |
| `document.print_charts` | Dokumen & Cetak | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `document.watermark_enabled` | Dokumen & Cetak | boolean | `boolean` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.watermark_opacity` | Dokumen & Cetak | decimal | `numeric\|min:0\|max:1` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.signature_mode` | Dokumen & Cetak | select | `nullable\|string\|max:100` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `document.template` | Dokumen & Cetak | select | `nullable\|string\|max:100` | app\Services\PrintDocumentService.php | Dibaca oleh runtime: app\Services\PrintDocumentService.php | — | **PARTIAL** |
| `numbering.invoice_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.invoice_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.po_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.po_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.pr_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.pr_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.do_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.do_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.gr_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.gr_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.weighbridge_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.weighbridge_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.journal_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.journal_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.work_order_prefix` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `numbering.work_order_format` | Nomor Dokumen | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `email.sender_name` | Email | text | `nullable\|string\|max:1000` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `email.sender_address` | Email | text | `nullable\|email\|max:255` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `email.reply_to` | Email | text | `nullable\|email\|max:255` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `email.logo` | Email | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `email.footer` | Email | textarea | `nullable\|string\|max:5000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `email.smtp_host` | Email | text | `nullable\|string\|max:1000` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `email.smtp_port` | Email | integer | `integer\|min:1\|max:65535` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `email.smtp_username` | Email | text | `nullable\|string\|max:1000` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `email.smtp_password` | Email | secret | `nullable\|string\|max:2000` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | tests\Unit\SettingsImportExportTest.php | **PARTIAL** |
| `email.smtp_encryption` | Email | select | `nullable\|string\|max:100` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **PARTIAL** |
| `pwa.enabled` | PWA | boolean | `boolean` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `pwa.name` | PWA | text | `nullable\|string\|max:1000` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `pwa.short_name` | PWA | text | `nullable\|string\|max:1000` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `pwa.description` | PWA | text | `nullable\|string\|max:1000` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `pwa.icon_192` | PWA | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `pwa.icon_512` | PWA | image | `nullable\|file\|mimes:png,jpg,jpeg,webp,ico\|max:5120` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `pwa.theme_color` | PWA | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `pwa.background_color` | PWA | color | `nullable, regex:/^#[0-9a-fA-F]{6}$/` | app\Http\Controllers\PwaManifestController.php | Dibaca oleh runtime: app\Http\Controllers\PwaManifestController.php | — | **WORKING** |
| `payroll.ptkp_monthly` | Payroll & Pajak | currency (Rp) | `numeric\|min:0` | app\Services\PayrollService.php | Dibaca oleh runtime: app\Services\PayrollService.php | — | **PARTIAL** |
| `payroll.pph21_rate` | Payroll & Pajak | percentage (%) | `numeric\|min:0\|max:100` | app\Services\PayrollService.php, app\Support\HumanLabel.php | Dibaca oleh runtime: app\Services\PayrollService.php, app\Support\HumanLabel.php | tests\Unit\SettingValidationTest.php | **PARTIAL** |
| `payroll.bpjs_health_employee_rate` | Payroll & Pajak | percentage (%) | `numeric\|min:0\|max:100` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | tests\Unit\SettingCatalogTest.php | **PARTIAL** |
| `payroll.bpjs_health_company_rate` | Payroll & Pajak | percentage (%) | `numeric\|min:0\|max:100` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | — | **PARTIAL** |
| `payroll.bpjs_employment_employee_rate` | Payroll & Pajak | percentage (%) | `numeric\|min:0\|max:100` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | — | **PARTIAL** |
| `payroll.bpjs_employment_company_rate` | Payroll & Pajak | percentage (%) | `numeric\|min:0\|max:100` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | — | **PARTIAL** |
| `payroll.overtime_rate` | Payroll & Pajak | decimal (x) | `numeric\|min:0` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | — | **PARTIAL** |
| `payroll.payday` | Payroll & Pajak | integer (tanggal) | `integer\|min:1\|max:31` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | — | **PARTIAL** |
| `payroll.cutoff_day` | Payroll & Pajak | integer (tanggal) | `integer\|min:1\|max:31` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | — | **PARTIAL** |
| `finance.fiscal_year_start` | Keuangan | integer (bulan) | `integer\|min:1\|max:12` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | — | **PARTIAL** |
| `finance.lock_posted_journal` | Keuangan | boolean | `boolean` | — | Belum ada consumer runtime | tests\Unit\SettingValidationTest.php | **NOT WIRED** |
| `finance.require_balanced_journal` | Keuangan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `finance.period_lock_enabled` | Keuangan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `finance.allow_backdate` | Keuangan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `finance.backdate_days` | Keuangan | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `budget.enforce` | Keuangan | select | `nullable\|string\|max:100` | app\Services\BudgetService.php | Dibaca oleh runtime: app\Services\BudgetService.php | tests\Unit\SettingCatalogTest.php | **PARTIAL** |
| `sales.invoice_prefix` | Penjualan | text | `nullable\|string\|max:1000` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | tests\Unit\SettingCatalogTest.php | **PARTIAL** |
| `sales.invoice_require_do` | Penjualan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `sales.credit_limit_enforced` | Penjualan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `sales.allow_negative_customer_balance` | Penjualan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `sales.require_contract` | Penjualan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `sales.default_payment_term_days` | Penjualan | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `inventory.allow_negative_stock` | Inventory | boolean | `boolean` | app\Docs\Content\Part1.php, app\Services\StockService.php | Dibaca oleh runtime: app\Docs\Content\Part1.php, app\Services\StockService.php | — | **PARTIAL** |
| `inventory.require_approval_adjustment` | Inventory | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `inventory.require_approval_transfer` | Inventory | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `inventory.low_stock_threshold` | Inventory | decimal | `numeric\|min:0` | app\Support\HumanLabel.php | Dibaca oleh runtime: app\Support\HumanLabel.php | tests\Unit\SettingLabelTest.php | **PARTIAL** |
| `inventory.costing_method` | Inventory | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `tax.default_sales_tax_code` | Pajak | text | `nullable\|string\|max:1000` | app\Docs\Content\Part1.php, app\Http\Controllers\SalesOrderController.php, app\Services\SalesService.php | Dibaca oleh runtime: app\Docs\Content\Part1.php, app\Http\Controllers\SalesOrderController.php, app\Services\SalesService.php | — | **PARTIAL** |
| `tax.default_purchase_tax_code` | Pajak | text | `nullable\|string\|max:1000` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `tax.npwp_required` | Pajak | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `tax.tax_invoice_enabled` | Pajak | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `procurement.po_over_receipt_tolerance` | Pengadaan | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `procurement.price_variance_tolerance` | Pengadaan | percentage (%) | `numeric\|min:0\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `procurement.require_approved_pr` | Pengadaan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `procurement.require_vendor` | Pengadaan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `procurement.three_way_match_enabled` | Pengadaan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fuel.dip_threshold_pct` | Fleet & BBM | percentage (%) | `numeric\|min:0\|max:100` | app\Services\FuelService.php | Dibaca oleh runtime: app\Services\FuelService.php | — | **PARTIAL** |
| `fuel.max_variance_pct` | Fleet & BBM | percentage (%) | `numeric\|min:0\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fuel.require_hm` | Fleet & BBM | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fuel.require_operator` | Fleet & BBM | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fuel.require_vehicle` | Fleet & BBM | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fuel.negative_tank_allowed` | Fleet & BBM | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fleet.maintenance_warning_hours` | Maintenance | integer (HM) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fleet.service_due_hours` | Maintenance | integer (HM) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `fleet.inactive_days_threshold` | Fleet & BBM | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `mining.production_unit` | Operasi Tambang | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `mining.production_cutoff_time` | Operasi Tambang | time | `date_format:H:i` | — | Belum ada consumer runtime | tests\Unit\SettingValidationTest.php | **NOT WIRED** |
| `mining.stock_reconciliation_tolerance` | Operasi Tambang | percentage (%) | `numeric\|min:0\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `mining.require_supervisor_approval` | Operasi Tambang | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `weighbridge.allow_weight_override` | Timbangan | boolean | `boolean` | app\Docs\Content\Part1.php, app\Http\Controllers\WeighbridgeTicketController.php | Dibaca oleh runtime: app\Docs\Content\Part1.php, app\Http\Controllers\WeighbridgeTicketController.php | — | **PARTIAL** |
| `weighbridge.void_require_approval` | Timbangan | boolean | `boolean` | app\Http\Controllers\WeighbridgeTicketController.php | Dibaca oleh runtime: app\Http\Controllers\WeighbridgeTicketController.php | — | **PARTIAL** |
| `weighbridge.auto_capture_enabled` | Timbangan | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `weighbridge.stable_weight_seconds` | Timbangan | integer (detik) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `weighbridge.minimum_weight` | Timbangan | decimal (kg) | `numeric\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `weighbridge.maximum_variance_pct` | Timbangan | percentage (%) | `numeric\|min:0\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `weighbridge.device_mode` | Timbangan | select | `nullable\|string\|max:100` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `hse.permit_expiry_warning_days` | HSE & Compliance | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `hse.medical_expiry_warning_days` | HSE & Compliance | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `hse.training_expiry_warning_days` | HSE & Compliance | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `hse.require_investigation` | HSE & Compliance | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `hse.require_supervisor_verification` | HSE & Compliance | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `hse.severity_levels` | HSE & Compliance | multiselect | `nullable\|array` | app\Services\HseService.php | Dibaca oleh runtime: app\Services\HseService.php | — | **PARTIAL** |
| `inventory.default_warehouse_id` | Inventory | model_select | `nullable\|integer\|exists:warehouses,id` | app\Http\Controllers\SalesOrderController.php | Dibaca oleh runtime: app\Http\Controllers\SalesOrderController.php | tests\Unit\ModelSelectSettingTest.php | **PARTIAL** |
| `mining.default_site_id` | Operasi Tambang | model_select | `nullable\|integer\|exists:sites,id` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `mining.default_pit_id` | Operasi Tambang | model_select | `nullable\|integer\|exists:pits,id` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.email_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.whatsapp_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.in_app_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.approval_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.low_stock_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.fuel_variance_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.hse_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.maintenance_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.contract_expiry_enabled` | Notifikasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.channels` | Notifikasi | multiselect | `nullable\|array` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.reminder_days` | Notifikasi | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.escalation_hours` | Notifikasi | integer (jam) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `security.session_timeout` | Keamanan | integer (menit) | `integer\|min:0` | app\Providers\AppServiceProvider.php | Dibaca oleh runtime: app\Providers\AppServiceProvider.php | — | **WORKING** |
| `security.password_min_length` | Keamanan | integer (karakter) | `integer\|min:8\|max:128` | app\Support\PasswordPolicy.php | Dibaca oleh runtime: app\Support\PasswordPolicy.php | — | **WORKING** |
| `security.password_require_uppercase` | Keamanan | boolean | `boolean` | app\Support\PasswordPolicy.php | Dibaca oleh runtime: app\Support\PasswordPolicy.php | — | **WORKING** |
| `security.password_require_number` | Keamanan | boolean | `boolean` | app\Support\PasswordPolicy.php | Dibaca oleh runtime: app\Support\PasswordPolicy.php | — | **WORKING** |
| `security.password_require_symbol` | Keamanan | boolean | `boolean` | app\Support\PasswordPolicy.php | Dibaca oleh runtime: app\Support\PasswordPolicy.php | — | **WORKING** |
| `security.mfa_enabled` | Keamanan | boolean | `boolean` | — | Belum ada consumer runtime | tests\Unit\SettingPermissionTest.php | **NOT WIRED** |
| `security.max_login_attempts` | Keamanan | integer (percobaan) | `integer\|min:0` | app\Http\Requests\Auth\LoginRequest.php | Dibaca oleh runtime: app\Http\Requests\Auth\LoginRequest.php | — | **WORKING** |
| `security.lockout_minutes` | Keamanan | integer (menit) | `integer\|min:0` | app\Http\Requests\Auth\LoginRequest.php | Dibaca oleh runtime: app\Http\Requests\Auth\LoginRequest.php | — | **WORKING** |
| `security.force_password_change_days` | Keamanan | integer (hari) | `integer\|min:0` | app\Http\Middleware\CheckPermission.php | Dibaca oleh runtime: app\Http\Middleware\CheckPermission.php | — | **WORKING** |
| `developer_labels_enabled` | Advanced | boolean | `boolean` | app\Http\Controllers\SettingController.php | Dibaca oleh runtime: app\Http\Controllers\SettingController.php | tests\Unit\SettingPermissionTest.php | **WORKING** |
| `docs.public` | Dokumen & Cetak | boolean | `boolean` | app\Docs\Content\Part1.php, app\Docs\DocRegistry.php | Dibaca oleh runtime: app\Docs\Content\Part1.php, app\Docs\DocRegistry.php | tests\Feature\DocsRouteTest.php | **PARTIAL** |
| `modules.fleet_enabled` | Integrasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `modules.fuel_enabled` | Integrasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `modules.hse_enabled` | Integrasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `modules.quality_enabled` | Integrasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `modules.dispatch_enabled` | Integrasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `modules.ai_enabled` | Integrasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `modules.telematics_enabled` | Integrasi | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `backup.enabled` | Backup & Data | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `backup.database_enabled` | Backup & Data | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `backup.files_enabled` | Backup & Data | boolean | `boolean` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `backup.retention_days` | Backup & Data | integer (hari) | `integer\|min:0` | — | Belum ada consumer runtime | — | **NOT WIRED** |
| `notification.whatsapp_webhook_url` | Integrasi | secret | `nullable\|string\|max:2000` | app\Docs\Content\Part1.php, app\Notifications\SystemAlert.php | Dibaca oleh runtime: app\Docs\Content\Part1.php, app\Notifications\SystemAlert.php | tests\Unit\SettingAuditTest.php | **PARTIAL** |

## Ringkasan

- Total metadata: **212**
- NOT WIRED / UI-only: **99**
- Raw key default UI: **0** (developer key hanya pada mode Advanced Super Admin).
- Catatan: setting berstatus NOT WIRED sengaja tidak diklaim selesai dan menjadi backlog wiring behavior.
