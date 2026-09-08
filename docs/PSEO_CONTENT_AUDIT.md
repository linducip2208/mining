# pSEO Content Audit

Hasil `php artisan seo:audit` (Tier 2, 2026-09-08):

| Metrik | Nilai |
|---|---|
| Generated | 10.620 |
| Indexable | 1000 (tier 2) |
| Noindex | 0 |
| Duplicates (title/desc/h1) | 0 / 0 / 0 |
| Thin pages (quality < 60) | 0 |
| Orphans | 0 |
| Broken internal links | 0 (`SeoQualityService::brokenInternalLinks`, output `seo:audit`) |
| Sitemap pages | 1000 (5 children) |
| Quality distribution (indexable) | PASS 944 · WARNING 56 · FAIL 0 |

Verifikasi ulang final (2026-09-08, sesi audit produksi): `seo:audit` →
PASS=469 · WARNING=31 · FAIL=0 · broken links 0 · `seo:sitemap` → 1.000 urls.

Riwayat: Tier 1 (300 indexable, PASS 279 · WARNING 21 · FAIL 0).

WARNING yang tersisa bersifat minor (mis. workflow 3 langkah vs ideal 4+)
dan tetap memenuhi threshold publish. FAIL = 0 sehingga tidak ada halaman
yang lolos tanpa gate.

Kandidat valid total (dry-run): ±10.584 dari kapasitas 22.000.
Tier berikutnya hanya dibuka setelah audit tier berjalan PASS dan metrik
orphan/duplicate/broken tetap nol.
