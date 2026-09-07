# Legacy Admin Import Audit

Alur: Tools → Import Data → UPLOAD → MAP → VALIDATE → PREVIEW → IMPORT.
Tidak ada write sebelum eksekusi eksplisit. Rerun file yang sama tidak
menggandakan transaksi (dedupe natural key + skip counter).

## Format batch

| Field | Arti |
|---|---|
| type | sparepart_master, opening_stock, letter_register, legacy_invoice, legacy_receipt |
| column_map | header CSV → field sistem (dipilih di UI mapping) |
| preview | 20 baris pertama hasil validasi |
| total/valid/warning/failed/imported/skipped | ringkasan per batch |
| error_report_path | CSV error per baris (ROW, FIELD, VALUE, ERROR), dapat diunduh |
| post_accounting | default false — legacy tidak menjurnal otomatis |

## Mapping per tipe

| Spreadsheet Source | Target Module | Target Table/Model | Mapping kunci |
|---|---|---|---|
| Master Sparepart (KODE, NAMA, KATEGORI, SATUAN, LOKASI, ...) | Sparepart Master | items (type=SPAREPART) | KODE→item.code (unik, duplikat skip), KATEGORI→category (auto-create), SATUAN→unit (auto-create), LOKASI→storage_location (harus ada), SUPPLIER→preferred (fuzzy, boleh kosong) |
| Barang Masuk STOK LAMA | Opening Stock | stock_ledger (OPENING) | KODE→item (wajib ada), WAREHOUSE→code (wajib ada), QTY>0, COST→unit_cost/avg, KETERANGAN→reference |
| Register Surat | Register Surat | letter_registers | NOMOR→number (unik), JENIS→fuzzy letter_types, STATUS default ARCHIVED |
| Legacy Invoice | Register Invoice | invoices | NOMOR→number (unik, ≤50), CUSTOMER→wajib ada di master, TOTAL→subtotal=total, PPN=0 |
| Legacy Kwitansi | Register Kwitansi | receipts | NOMOR→number (unik), PAYER→payer_name, tanpa payment link (dokumen saja) |

## Imported / Rejected / Duplicate / Warning

- Duplikat (natural key sudah ada): SKIPPED, dihitung di skipped_rows.
- Baris tak valid (wajib kosong, angka rusak, master tak ditemukan): FAILED + error report, batch tetap bisa dieksekusi untuk baris valid? Tidak — eksekusi hanya bila failed = 0 (UI mengunci tombol).
- Warning (sudah ada tapi tetap diproses ulang aman): dihitung, tetap diimport bila idempoten (opening memakai import_batch_id LEGACY-{batch}).

## Accounting Impact

- Master/letter/invoice/receipt legacy: NOL jurnal.
- Opening stock: movement OPENING meng-update moving average (stock effect YA, jurnal TIDAK).
- Flag: semua baris `source = LEGACY_IMPORT` + `legacy_reference` (nama file) + `import_batch_id` untuk ledger.

## Stock Impact

- Hanya opening_stock yang menulis ledger (satu baris per item per batch; rerun skip).
- Tidak ada UPDATE/DELETE ledger; koreksi via adjustment/opname.

## Audit

Batch CREATE + EXECUTE tercatat (`IMPORT` di audit_logs dengan imported/skipped).
Hapus batch hanya bila belum IMPORTED (jejak audit dipertahankan).
