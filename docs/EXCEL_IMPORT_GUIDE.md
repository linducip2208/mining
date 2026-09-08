# Excel Import Guide — Mining ERP

Wizard **Tools → Import Data** + CLI `php artisan import:legacy`. Native reader
tanpa dependency tambahan (ZipArchive + SimpleXML untuk xlsx, BIFF8 reader untuk xls).

## Format file

| Format | Status | Catatan |
|---|---|---|
| `.xlsx` | didukung penuh | multi-sheet, shared strings, tanggal serial Excel |
| `.xls` (BIFF8, Excel 97-2003) | didukung | teks/angka/formula-cached; tanpa style lengkap |
| `.csv` / `.txt` | didukung | pemisah koma standar |
| `.xlsm`, `.xlsb`, `.xla` | **ditolak** | macro-enabled berisiko — simpan ulang sebagai .xlsx/.csv |

Maksimal 5.000 baris/batch, ukuran file maksimal 20 MB. Nama file tersimpan
dirandom oleh storage Laravel; download error report melalui controller berotorisasi.

## Flow wizard

```
UPLOAD (pilih tipe + perusahaan + mode)
→ PILIH SHEET (bila workbook > 1 sheet)
→ MAP (auto-map via alias, bisa di-override)
→ VALIDATE (per baris: VALID / WARNING / ERROR / DUPLICATE)
→ PREVIEW (20 baris pertama + jumlah error)
→ IMPORT (wajib force bila file identik pernah diimport)
```

## Tipe & mode (PART 35)

| Tipe | Mode | Default | Efek akuntansi |
|---|---|---|---|
| Master Sparepart | — | — | tidak ada |
| Opening Stock | STOCK_ONLY | ✔ | ledger saja |
| Opening Stock | STOCK_AND_ACCOUNTING | | + jurnal Dr Inventory / Cr Opening Balance Equity |
| Register Surat | — | — | tidak ada |
| Register Invoice Lama | REGISTER_ONLY | ✔ | tidak ada (record only) |
| Register Invoice Lama | OPENING_AR | | + jurnal Dr AR / Cr Opening Balance Equity (non-PAID) |
| Register Kwitansi Lama | HISTORY_ONLY | ✔ | tidak ada (kwitansi tidak menciptakan uang) |
| Register Kwitansi Lama | OPENING_PAYMENT | | + jurnal Dr Kas / Cr Opening Balance Equity |

Mode default mencegah double accounting pada dokumen finansial lama.

## Normalisasi otomatis

**Header (PART 32)** — uppercase, spasi/underscore/dash/titik disatukan, alias
dikenali: `NO SURAT`/`NOMOR SURAT`/`NO. SURAT` → number; `KODE BARANG`/`KODE
SPAREPART` → code; `NAMA BARANG`/`NAMA SPAREPART` → name; `QTY`/`JUMLAH`/`SALDO`
→ qty (sesuai tipe); user tetap bisa override di langkah MAP.

**Tanggal (PART 33)** — `YYYY-MM-DD`, `DD/MM/YYYY`, `DD-MM-YYYY`, serial Excel
(30000–60000 → tanggal). Ketika hari & bulan keduanya ≤ 12 (mis. `03/05/2024`)
baris ditandai **WARNING ambigu** — dd/mm (locale ID) yang dipakai, tidak pernah
di-tafsir diam-diam. Tanggal tidak valid → ERROR, bukan today().

**Angka & mata uang (PART 34)** — `Rp 12.000.000` → 12000000; `12.000.000` →
12000000; `12,000,000` → 12000000; `700` → 700; `2.000` → 2000; `2,5` → 2.5;
`(1.500)` → −1500.

## Idempotensi

1. **File hash** — SHA-256 disimpan per batch. Batch berisi konten identik yang
   sudah pernah diimport memicu peringatan dan mewajibkan centang
   *"Import ulang paksa"* (`IMPORT_FORCE` ter-audit).
2. **Row fingerprint** — SHA-256 dari (tipe, company, site, warehouse, nomor
   dokumen/tanggal/item/qty/nilai). Disimpan persisten di tabel
   `import_row_fingerprints` dengan unique index — duplikat lintas batch
   otomatis di-skip.

## Dry-run (PART 38)

```
php artisan import:legacy --file="master.xlsx" --type=sparepart_master \
  --sheet="MASTER SPAREPART" --company=1 --warehouse=1 --dry-run
```

`--dry-run` **tidak menulis apa pun** (tidak membuat batch, tidak menyentuh
data). Output: tabel baris + status VALID/WARNING/ERROR + ringkasan.

## Error report (PART 40)

Tombol *Errors CSV* mengunduh `ROW, COLUMN, VALUE, ERROR, SUGGESTED ACTION`
(UTF-8 BOM, aman dibuka Excel).

## Rollback / reversal (PART 41)

* Batch belum `IMPORTED` → boleh dihapus.
* Batch register (letter/invoice/receipt) → baris yang dibuat dapat dihapus
  (belum ada downstream) dan batch ditandai ROLLED_BACK — setiap aksi ter-audit.
* Opening stock yang ledger-nya sudah ada → **tidak pernah di-delete**; buat
  movement reversal `OPENING_REVERSAL` (audit memaksa jejak). Jurnal tidak ikut
  dibalik otomatis — gunakan reversal jurnal via AccountingService.

## Audit trail

Setiap upload/execute/force tercatat di `audit_logs`
(action CREATE/IMPORT_FORCE/CREATE, detail type/mode/sheet/hasil).
