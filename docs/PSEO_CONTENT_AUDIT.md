# pSEO Content Audit

Hasil `php artisan seo:audit` (Tier 1, 2026-09-07):

| Metrik | Nilai |
|---|---|
| Generated | 300 |
| Indexable | 300 |
| Noindex | 0 |
| Duplicates (title/desc/h1) | 0 / 0 / 0 |
| Thin pages (quality < 60) | 0 |
| Orphans | 0 |
| Broken internal links | 0 (related + hub terverifikasi via test) |
| Sitemap pages | 300 (5 children: core, module, usecase, industry, location) |
| Quality distribution | PASS 279 · WARNING 21 · FAIL 0 |
| Avg quality | ≥ 60 (gate) |

WARNING yang tersisa bersifat minor (mis. workflow 3 langkah vs ideal 4+)
dan tetap memenuhi threshold publish. FAIL = 0 sehingga tidak ada halaman
yang lolos tanpa gate.

Kandidat valid total (dry-run): ±10.584 dari kapasitas 22.000.
Tier berikutnya hanya dibuka setelah audit tier berjalan PASS dan metrik
orphan/duplicate/broken tetap nol.
