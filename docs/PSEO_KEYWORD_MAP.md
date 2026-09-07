# pSEO Keyword Map

Sumber: `SeoSeeder::keywords()` (53 keyword) + cluster modul/industri/lokasi.
Intent: BUY, PRICE, SOURCE_CODE, SOFTWARE, APPLICATION, FEATURE, INDUSTRY,
LOCATION, COMPARISON, CUSTOM, INTEGRATION.

## Cluster A — Source Code (commercial 75–100)

source code erp mining · source code erp tambang · jual/beli/harga source
code erp mining|tambang · source code aplikasi tambang · source code software
pertambangan · source code sistem pertambangan · source code manajemen tambang

## Cluster B — Software (commercial 70–90)

software mining|tambang|pertambangan|perusahaan tambang · software manajemen|
operasional|produksi|inventory|accounting tambang · software ERP pertambangan

## Cluster C — Aplikasi (commercial 65–85)

aplikasi tambang|pertambangan|mining|perusahaan tambang · aplikasi operasional|
produksi|stok|timbangan|fleet|BBM|maintenance tambang

## Cluster D — ERP (commercial 75–100)

erp mining|tambang|pertambangan · mining erp indonesia · erp perusahaan
tambang|pertambangan · sistem erp tambang|pertambangan · erp tambang
terintegrasi · erp mining management system

## Cluster Modul (intent FEATURE, commercial 65–85)

software fuel management tambang · aplikasi bbm pertambangan · software fleet
management mining · aplikasi maintenance alat berat tambang · software
weighbridge pertambangan · aplikasi timbangan truk tambang · software stockpile
management · software crusher production · software dispatch hauling tambang ·
aplikasi payroll perusahaan tambang · software accounting perusahaan
pertambangan

## Pillar (commercial 85–100)

`/erp-mining` · `/erp-tambang` · `/source-code-erp-mining` ·
`/source-code-erp-tambang` · `/software-pertambangan` ·
`/aplikasi-pertambangan` · `/harga-erp-tambang` · `/fitur-erp-mining`

## Industri (20)

Batubara · Nikel · Emas · Bauksit · Timah · Tembaga · Besi · Mangan · Pasir ·
Batu · Quarry · Stone Crusher · Mining/Kontraktor Tambang · Hauling ·
Heavy Equipment · Mineral Processing · Smelter · Port/Jetty · Stockpile Operator

## Lokasi

Indonesia + 27 provinsi + kota/kabupaten relevan (prioritas Kalimantan,
Sumatera, Sulawesi, Papua). Wording aman, tanpa klaim fisik.

## Aturan kombinasi valid

CORE + MODULE + INDUSTRY + LOCATION + USE CASE, terkontrol per rule
(`seo:generate --dry-run`). Maksimum 22.000; katalog saat ini ±10.584.
Kombinasi tak natural / tanpa intent / tanpa peluang konten unik ditolak
generator (`validCandidate`) atau gate (`FAIL` → noindex).
