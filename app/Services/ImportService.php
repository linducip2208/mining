<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\LetterRegister;
use App\Models\LetterType;
use App\Models\Receipt;
use App\Models\StockLedger;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Legacy spreadsheet import: UPLOAD → MAP → VALIDATE → PREVIEW → IMPORT.
 * Idempotent via natural keys (code/number) + batch fingerprint log.
 * Legacy rows never auto-post journals unless explicitly configured.
 */
final class ImportService
{
    /**
     * @return array<string, array{label:string, fields:array<string,string>, required:string[]}>
     */
    public static function types(): array
    {
        return [
            'sparepart_master' => ['label' => 'Master Sparepart', 'fields' => [
                'code' => 'KODE', 'name' => 'NAMA SPAREPART', 'category' => 'KATEGORI',
                'unit' => 'SATUAN', 'location' => 'LOKASI', 'brand' => 'BRAND',
                'part_number' => 'PART NUMBER', 'min_stock' => 'MINIMUM STOCK',
                'max_stock' => 'MAXIMUM STOCK', 'reorder_point' => 'REORDER POINT',
                'supplier' => 'SUPPLIER', 'purchase_price' => 'PURCHASE PRICE',
            ], 'required' => ['code', 'name']],
            'opening_stock' => ['label' => 'Opening Stock / Stok Lama', 'fields' => [
                'date' => 'TANGGAL', 'code' => 'KODE SPAREPART', 'warehouse' => 'WAREHOUSE',
                'location' => 'RAK/BIN', 'qty' => 'QTY', 'cost' => 'COST',
                'reference' => 'KETERANGAN', 'notes' => 'CATATAN',
            ], 'required' => ['code', 'warehouse', 'qty']],
            'letter_register' => ['label' => 'Register Surat', 'fields' => [
                'number' => 'NOMOR SURAT', 'date' => 'TANGGAL', 'type' => 'JENIS SURAT',
                'subject' => 'PERIHAL', 'recipient' => 'TUJUAN', 'status' => 'STATUS',
                'notes' => 'KETERANGAN',
            ], 'required' => ['number', 'subject']],
            'legacy_invoice' => ['label' => 'Legacy Invoice', 'fields' => [
                'number' => 'NOMOR INVOICE', 'date' => 'TANGGAL', 'customer' => 'CUSTOMER',
                'total' => 'TOTAL', 'status' => 'STATUS', 'notes' => 'KETERANGAN',
            ], 'required' => ['number', 'total']],
            'legacy_receipt' => ['label' => 'Legacy Kwitansi', 'fields' => [
                'number' => 'NOMOR KWITANSI', 'date' => 'TANGGAL', 'payer' => 'CUSTOMER / PAYER',
                'amount' => 'AMOUNT', 'method' => 'PAYMENT METHOD', 'status' => 'STATUS',
            ], 'required' => ['number', 'amount']],
        ];
    }

    /**
     * @return array<int, array<string,string|null>>
     */
    public static function readCsv(ImportBatch $batch, int $maxRows = 5000): array
    {
        $path = Storage::path($batch->file_name);
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException('File tidak dapat dibaca.');
        }
        $headers = null;
        $rows = [];
        while (($line = fgetcsv($handle)) !== false && count($rows) < $maxRows) {
            if ($headers === null) {
                $headers = array_map(fn ($h) => trim((string) $h), $line);

                continue;
            }
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
        }
        fclose($handle);

        return ['headers' => $headers ?? [], 'rows' => $rows];
    }

    /**
     * @return array{headers:array, rows:array, total:int, valid:int, warnings:int, failed:int, errors:array}
     */
    public static function validate(ImportBatch $batch, array $columnMap): array
    {
        $def = self::types()[$batch->type];
        $data = self::readCsv($batch);
        $result = ['headers' => $data['headers'], 'rows' => [], 'total' => 0, 'valid' => 0, 'warnings' => 0, 'failed' => 0, 'errors' => []];
        foreach ($data['rows'] as $i => $csvRow) {
            $rowNum = $i + 2;
            $mapped = [];
            foreach ($columnMap as $csvHeader => $field) {
                if ($field && array_key_exists($csvHeader, $csvRow)) {
                    $mapped[$field] = trim((string) $csvRow[$csvHeader]);
                }
            }
            $errors = [];
            foreach ($def['required'] as $req) {
                if (($mapped[$req] ?? '') === '') {
                    $errors[] = "Kolom {$req} wajib diisi";
                }
            }
            $custom = self::validateRow($batch->type, $mapped, $rowNum);
            $errors = array_merge($errors, $custom['errors']);
            $result['total']++;
            $mapped['_row'] = $rowNum;
            $mapped['_fingerprint'] = SparepartService::fingerprint([$batch->type, ...array_values($mapped)]);
            if ($errors !== []) {
                $result['failed']++;
                foreach ($errors as $e) {
                    $result['errors'][] = ['row' => $rowNum, 'error' => $e];
                }
            } elseif ($custom['warning']) {
                $result['warnings']++;
            } else {
                $result['valid']++;
            }
            if (count($result['rows']) < 20) {
                $result['rows'][] = $mapped + ['_errors' => $errors];
            }
        }

        return $result;
    }

    /**
     * @return array{errors:string[], warning:bool}
     */
    protected static function validateRow(string $type, array $row, int $rowNum): array
    {
        $errors = [];
        $warning = false;
        $numeric = fn ($v) => $v === '' || $v === null || is_numeric(str_replace([',', ' '], '', (string) $v));
        foreach (['qty', 'cost', 'total', 'amount', 'min_stock', 'max_stock', 'reorder_point', 'purchase_price'] as $num) {
            if (isset($row[$num]) && ! $numeric($row[$num])) {
                $errors[] = "Kolom {$num} harus angka";
            }
        }
        if (in_array($type, ['sparepart_master', 'opening_stock'], true) && ($row['code'] ?? '') !== '') {
            $exists = Item::where('code', $row['code'])->exists();
            if ($type === 'sparepart_master' && $exists) {
                $warning = true;
            }
        }
        if ($type === 'opening_stock') {
            if (($row['warehouse'] ?? '') !== '' && ! Warehouse::where('code', $row['warehouse'])->exists()) {
                $errors[] = "Warehouse '{$row['warehouse']}' tidak ditemukan";
            }
            if (($row['code'] ?? '') !== '' && ! Item::where('code', $row['code'])->exists()) {
                $errors[] = "Sparepart '{$row['code']}' tidak ditemukan di master";
            }
        }
        if ($type === 'legacy_invoice') {
            if (($row['customer'] ?? '') !== '' && ! Customer::where('name', 'like', '%'.$row['customer'].'%')->exists()) {
                $errors[] = "Customer '{$row['customer']}' tidak ditemukan di master";
            }
            if (strlen($row['number'] ?? '') > 50) {
                $errors[] = 'Nomor invoice maksimal 50 karakter';
            }
        }
        if (in_array($type, ['letter_register', 'legacy_invoice', 'legacy_receipt'], true) && ($row['number'] ?? '') !== '') {
            $exists = match ($type) {
                'letter_register' => LetterRegister::where('number', $row['number'])->exists(),
                'legacy_invoice' => Invoice::where('number', $row['number'])->exists(),
                default => Receipt::where('number', $row['number'])->exists(),
            };
            if ($exists) {
                $warning = true;
            }
        }

        return ['errors' => $errors, 'warning' => $warning];
    }

    /**
     * Execute a validated batch. Returns [imported, skipped].
     */
    public static function execute(ImportBatch $batch): array
    {
        $data = self::readCsv($batch);
        $map = $batch->column_map ?? [];
        $imported = 0;
        $skipped = 0;
        DB::transaction(function () use ($batch, $data, $map, &$imported, &$skipped) {
            foreach ($data['rows'] as $csvRow) {
                $mapped = [];
                foreach ($map as $csvHeader => $field) {
                    if ($field && array_key_exists($csvHeader, $csvRow)) {
                        $mapped[$field] = trim((string) $csvRow[$csvHeader]);
                    }
                }
                if (self::insertRow($batch, $mapped)) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }
            $batch->update([
                'status' => 'IMPORTED', 'imported_rows' => $imported, 'skipped_rows' => $skipped,
                'imported_by' => auth()->id(), 'imported_at' => now(),
            ]);
        });
        AuditService::log('CREATE', 'IMPORT', $batch->id, ImportBatch::class, null, ['type' => $batch->type, 'imported' => $imported, 'skipped' => $skipped]);

        return [$imported, $skipped];
    }

    protected static function num(mixed $v): float
    {
        return (float) str_replace([',', ' '], '', (string) ($v ?? 0));
    }

    /**
     * Insert one mapped row. Returns false when skipped as duplicate.
     */
    protected static function insertRow(ImportBatch $batch, array $row): bool
    {
        return match ($batch->type) {
            'sparepart_master' => self::insertSparepart($batch, $row),
            'opening_stock' => self::insertOpening($batch, $row),
            'letter_register' => self::insertLetter($batch, $row),
            'legacy_invoice' => self::insertLegacyInvoice($batch, $row),
            'legacy_receipt' => self::insertLegacyReceipt($batch, $row),
            default => false,
        };
    }

    protected static function insertSparepart(ImportBatch $batch, array $row): bool
    {
        if (($row['code'] ?? '') === '' || Item::where('code', $row['code'])->exists()) {
            return false;
        }
        $category = ItemCategory::firstOrCreate(['code' => strtoupper(substr($row['category'] ?? 'SPAREPART', 0, 20))], ['name' => $row['category'] ?? 'Sparepart', 'type' => 'SPAREPART']);
        $unit = Unit::firstOrCreate(['code' => strtoupper(substr($row['unit'] ?? 'PCS', 0, 10))], ['name' => $row['unit'] ?? 'Pcs']);
        $location = null;
        if (($row['location'] ?? '') !== '') {
            $location = StorageLocation::where('code', $row['location'])->first();
        }
        $supplier = null;
        if (($row['supplier'] ?? '') !== '') {
            $supplier = Supplier::where('name', 'like', '%'.$row['supplier'].'%')->first();
        }
        Item::create([
            'code' => $row['code'], 'name' => $row['name'] ?? $row['code'],
            'item_category_id' => $category->id, 'type' => 'SPAREPART', 'unit_id' => $unit->id,
            'brand' => $row['brand'] ?? null, 'part_number' => $row['part_number'] ?? null,
            'storage_location_id' => $location?->id,
            'min_stock' => self::num($row['min_stock'] ?? 0), 'max_stock' => self::num($row['max_stock'] ?? 0) ?: null,
            'reorder_point' => self::num($row['reorder_point'] ?? 0),
            'preferred_supplier_id' => $supplier?->id,
            'standard_cost' => self::num($row['purchase_price'] ?? 0),
            'last_purchase_price' => self::num($row['purchase_price'] ?? 0),
            'status' => true, 'source' => 'LEGACY_IMPORT',
            'legacy_reference' => $batch->file_name, 'created_by' => auth()->id(),
        ]);

        return true;
    }

    protected static function insertOpening(ImportBatch $batch, array $row): bool
    {
        $item = Item::where('code', $row['code'] ?? '')->first();
        $wh = Warehouse::where('code', $row['warehouse'] ?? '')->first();
        if (! $item || ! $wh) {
            return false;
        }
        $qty = self::num($row['qty'] ?? 0);
        if ($qty <= 0) {
            return false;
        }
        $duplicate = StockLedger::where('warehouse_id', $wh->id)->where('item_id', $item->id)
            ->where('movement_type', 'OPENING')->where('import_batch_id', 'LEGACY-'.$batch->id)->exists();
        if ($duplicate) {
            return false;
        }
        $location = null;
        if (($row['location'] ?? '') !== '') {
            $location = StorageLocation::where('code', $row['location'])->where('warehouse_id', $wh->id)->first();
        }
        $ledger = StockService::move($wh->id, $item->id, 'OPENING', $qty, 0, $wh->company_id, $wh->site_id, $batch->id, 'OPENING_BALANCE', $row['reference'] ?? ('STOK-LAMA-'.$batch->id), self::num($row['cost'] ?? 0) ?: null, self::parseDate($row['date'] ?? null));
        $ledger->update([
            'source' => 'LEGACY_IMPORT', 'import_batch_id' => 'LEGACY-'.$batch->id,
            'storage_location_id' => $location?->id, 'notes' => $row['notes'] ?? 'STOK LAMA',
        ]);

        return true;
    }

    protected static function insertLetter(ImportBatch $batch, array $row): bool
    {
        if (($row['number'] ?? '') === '' || LetterRegister::where('number', $row['number'])->exists()) {
            return false;
        }
        $type = LetterType::where('name', 'like', '%'.($row['type'] ?? '').'%')->orWhere('code', $row['type'] ?? '')->first()
            ?? LetterType::where('code', 'EXT')->first();
        $company = Company::first();
        LetterRegister::create([
            'number' => $row['number'], 'letter_type_id' => $type?->id,
            'letter_date' => self::parseDate($row['date'] ?? null),
            'subject' => $row['subject'] ?? '-', 'recipient_type' => 'OTHER',
            'recipient_name' => $row['recipient'] ?? null,
            'notes' => $row['notes'] ?? null,
            'status' => in_array(strtoupper($row['status'] ?? ''), LetterRegister::STATUSES, true) ? strtoupper($row['status']) : 'ARCHIVED',
            'company_id' => $company?->id, 'source' => 'LEGACY_IMPORT',
            'legacy_reference' => $batch->file_name, 'created_by' => auth()->id(),
        ]);

        return true;
    }

    protected static function insertLegacyInvoice(ImportBatch $batch, array $row): bool
    {
        if (($row['number'] ?? '') === '' || Invoice::where('number', $row['number'])->exists()) {
            return false;
        }
        $company = Company::first();
        $customer = null;
        if (($row['customer'] ?? '') !== '') {
            $customer = Customer::where('name', 'like', '%'.$row['customer'].'%')->first();
        }
        if (! $customer) {
            return false;
        }
        // Legacy: record only, never auto-journal historic amounts.
        Invoice::create([
            'number' => $row['number'], 'company_id' => $company?->id,
            'customer_id' => $customer?->id, 'invoice_date' => self::parseDate($row['date'] ?? null),
            'subtotal' => self::num($row['total'] ?? 0), 'tax_amount' => 0,
            'total' => self::num($row['total'] ?? 0), 'paid_amount' => 0,
            'status' => in_array(strtoupper($row['status'] ?? ''), ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED', 'VOID'], true) ? strtoupper($row['status']) : 'POSTED',
            'source' => 'LEGACY_IMPORT', 'legacy_reference' => $batch->file_name,
            'created_by' => auth()->id(),
        ]);

        return true;
    }

    protected static function insertLegacyReceipt(ImportBatch $batch, array $row): bool
    {
        if (($row['number'] ?? '') === '' || Receipt::where('number', $row['number'])->exists()) {
            return false;
        }
        // Legacy receipts without a payment link are recorded as documents only.
        $company = Company::first();
        Receipt::create([
            'number' => $row['number'], 'receipt_date' => self::parseDate($row['date'] ?? null),
            'company_id' => $company?->id, 'payment_id' => null,
            'payer_name' => $row['payer'] ?? '-', 'description' => 'Legacy import',
            'amount' => self::num($row['amount'] ?? 0),
            'payment_method' => in_array(strtoupper($row['method'] ?? ''), ['CASH', 'TRANSFER', 'GIRO', 'DEPOSIT'], true) ? strtoupper($row['method']) : 'TRANSFER',
            'status' => in_array(strtoupper($row['status'] ?? ''), Receipt::STATUSES, true) ? strtoupper($row['status']) : 'CONFIRMED',
            'source' => 'LEGACY_IMPORT', 'legacy_reference' => $batch->file_name,
            'created_by' => auth()->id(),
        ]);

        return true;
    }

    protected static function parseDate(?string $value): string
    {
        if (! $value) {
            return now()->toDateString();
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->toDateString();
            } catch (\Throwable) {
            }
        }

        return now()->toDateString();
    }
}
