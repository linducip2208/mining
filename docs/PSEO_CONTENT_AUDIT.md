# pSEO Content Audit

Hasil `php artisan seo:audit` (Tier 2, 2026-09-08):

| Metrik | Nilai |
|---|---|
| Generated | 1000 |
| Indexable | 1000 |
| Noindex | 0 |
| Duplicates (title/desc/h1) | 0 / 0 / 0 |
| Thin pages (quality < 60) | 0 |
| Orphans | 0 |
| Broken internal links | 0 (related + hub terverifikasi via test) |
| Sitemap pages | 1000 (5 children) |
| Quality distribution | PASS 944 · WARNING 56 · FAIL 0 |

Riwayat: Tier 1 (300 indexable, PASS 279 · WARNING 21 · FAIL 0).

WARNING yang tersisa bersifat minor (mis. workflow 3 langkah vs ideal 4+)
dan tetap memenuhi threshold publish. FAIL = 0 sehingga tidak ada halaman
yang lolos tanpa gate.

Kandidat valid total (dry-run): ±10.584 dari kapasitas 22.000.
Tier berikutnya hanya dibuka setelah audit tier berjalan PASS dan metrik
orphan/duplicate/broken tetap nol.
