# Receipt (Kwitansi) Workflow — Mining ERP

Kwitansi adalah **bukti pembayaran**, bukan penciptaan uang. Sumber jurnal
selalu di `SalesService::receivePayment` (Dr Kas/Bank → Cr AR). Register kwitansi
tidak pernah memposting jurnal sendiri.

## Sumber & aturan

```
PAYMENT (POSTED, jurnal sudah ada)
→ RECEIPT DRAFT   (nomor via NumberingService KWITANSI, concurrency-safe)
→ ISSUED          (approved_by terisi)
→ CONFIRMED
→ VOID            (payment asal tidak tersentuh — tidak ada uang yang dibatalkan)
```

**Guard:**

1. Hanya payment `POSTED` yang boleh dibuatkan kwitansi.
2. **Satu kwitansi aktif per payment** — kwitansi ganda ditolak (void kwitansi
   lama dulu bila perlu menerbitkan ulang). Payment yang dibatalkan (VOID)
   membebaskan slot.
3. Nomor kwitansi unik (unique index) + counter `KWITANSI` lockForUpdate —
   aman konkuren, reset bulanan, nomor void tidak dipakai ulang.
4. Amount kwitansi = amount payment (tidak bisa ditulis bebas).
5. Semua aksi (create/issue/confirm/void/print) ter-audit; print tercatat
   `printed_at` + audit PRINT.

## Satu payment → banyak invoice

Payment dialokasikan FIFO ke invoice (`payment_allocations`): alokasi per
invoice = min(sisa tagihan, sisa dana). Total alokasi ≤ total payment
(overpay ditolak — transaksi rollback). Kwitansi **menampilkan daftar alokasi**
(invoice + tanggal + jumlah) pada detail dan cetak.

## Satu invoice → banyak payment

Invoice menampilkan Total, Paid (dari `paid_amount` yang selalu = Σ alokasi
payment + alokasi deposit), Outstanding, status PAID/PARTIALLY_PAID.
Rekonsiliasi dipaksa oleh `php artisan accounting:audit-integrity`
(paid_amount ≠ alokasi → exit 1).

## Deposit

Alokasi deposit ke invoice (Dr CUSTOMER_DEPOSIT / Cr AR) juga menaikkan
paid_amount tanpa membuat row Payment — direkonsiliasi terpisah oleh audit
(Σ PaymentAllocation + Σ Deposit DEPOSIT_USED ref INVOICE = paid_amount).

## Cetak kwitansi

Template `print/receipt` (Print Engine + Paper Profile, tanpa branding
hardcoded): nomor, tanggal, received from (pelanggan/alamat), jumlah,
**terbilang** (NumberToWordsService), tujuan pembayaran, daftar alokasi invoice,
metode bayar, bank/kas + reference, tanda tangan, dibuat/disetujui/dicetak,
dan marker **`*** SALINAN / REPRINT ***`** pada cetakan kedua dst.

## Import legacy

Kwitansi lama diimport sebagai register (HISTORY_ONLY, default — tanpa jurnal)
atau OPENING_PAYMENT (Dr Kas / Cr Opening Balance Equity). Lihat
`docs/EXCEL_IMPORT_GUIDE.md`.

## Permission

| Aksi | Permission |
|---|---|
| Lihat register/detail | `receipt.view` |
| Buat kwitansi | `receipt.create` |
| Cetak | `receipt.print` |
| Void | `receipt.void` |
