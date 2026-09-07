# pSEO Architecture — Mining ERP Sales Engine

Data-driven programmatic SEO untuk menjual source code ERP mining
(mulai Rp12.000.000, CTA WhatsApp 081296052010 / 6281296052010).
22.000 adalah CAPACITY HARD CAP, bukan target publish.

## Komponen

| Layer | Implementasi |
|---|---|
| Models | `SeoPage`, `SeoKeyword`, `SeoLocation`, `SeoIndustry`, `SeoUseCase`, `SeoFeature`, `SeoCtaClick` |
| Generator | `SeoPageGenerator` (MAX_INDEXABLE_PAGES = 22000, tier 1–5) |
| Content | `SeoContentService` (deterministik, tanpa spun text) |
| Links | `SeoInternalLinkService` (related ≤12, hub di pillar, orphan target 0) |
| Schema | `SeoSchemaService` (Organization, WebPage, SoftwareApplication+Offer IDR, BreadcrumbList, FAQPage; tanpa rating/review) |
| Sitemap | `SeoSitemapService` (index + children per cluster, cache, hanya indexable) |
| Quality | `SeoQualityService` (PASS/WARNING/FAIL, threshold 60/60) |
| WhatsApp | `WhatsappService` (satu-satunya pembuat link wa.me; nomor ternormalisasi) |
| Controller | `SeoLandingController` (landing, finder noindex, cta-click, sitemap, robots) |
| Admin | `MarketingSeoController` + sidebar Marketing (permission `marketing.*`) |
| Views | `seo/landing`, `seo/finder`, `x-marketing.*` (11 komponen) |
| Routes | `routes/seo.php` (di-require PALING AKHIR di web.php) + catch-all aman |
| Commands | `seo:generate`, `seo:audit`, `seo:sitemap` |
| Seeder | `SeoSeeder` (idempoten, ±54 fitur, 20 industri, ±98 lokasi, 12 use case, 53 keyword) |

## Alur generate → publish

```
GENERATE (upsert by fingerprint)
  → compose content (deterministik)
  → content_hash / version
  → QUALITY CHECK (gate)
  → PASS/WARNING + dalam tier cap → PUBLISHED/indexable
  → FAIL → DRAFT + noindex_reason
DUPLICATE CHECK (title/h1/desc unik → FAIL bila duplikat)
INTERNAL LINK CHECK (related + hub pillar)
REVIEW/PUBLISH (admin dapat publish/noindex/archive manual)
```

## Status page

DRAFT → REVIEW → PUBLISHED; NOINDEX (manual/gagal gate); ARCHIVED
(301 ke `redirect_to` bila diisi, else 410). DRAFT/REVIEW tidak dirender
publik (404). Sitemap hanya `PUBLISHED + indexable`.

## Tier rollout

Tier 1: 300 → Tier 2: 1.000 → Tier 3: 5.000 → Tier 4: 10.000 → Tier 5: 22.000.
Tier lanjut hanya bila audit PASS, duplicate terkendali, orphan = 0,
broken links = 0, performa OK. Kapasitas katalog saat ini: ±10.584 kombinasi
valid (dry-run), di bawah cap.

## Aturan komersial

- Harga SELALU Rp12.000.000 / Rp12 Juta + catatan "harga mulai".
- WhatsApp SELALU via `WhatsappService`, atribusi source=pseo.
- Klaim fitur HANYA dari `SeoFeature` implemented + marketing_enabled.
- Lokasi: "untuk perusahaan di X", tanpa klaim kantor/cabang fisik.
- Tanpa superlatif, statistik, maupun review palsu (gate menolak otomatis).
- Lisensi: "Detail lisensi source code mengikuti penawaran/perjanjian."
