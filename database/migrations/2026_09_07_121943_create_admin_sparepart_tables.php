<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Letter types (configurable, replaces hardcoded categories) ----
        Schema::create('letter_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('SP, HRD, SK, BAST...');
            $table->string('name', 150);
            $table->string('numbering_format', 200)->default('{SEQ:3}/{TYPE}-{COMPANY}/{MONTH_ROMAN}/{YEAR}');
            $table->string('reset_period', 20)->default('YEARLY')->comment('YEARLY, MONTHLY, NEVER');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ---- Letter number reservations (RESERVED/USED/CANCELLED/VOID) ----
        // NOTE: created after letter_registers (FK dependency).

        // ---- Letter register ----
        Schema::create('letter_registers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100)->unique()->nullable();
            $table->foreignId('letter_type_id')->constrained()->restrictOnDelete();
            $table->date('letter_date');
            $table->string('subject', 255);
            $table->text('body')->nullable();
            $table->string('recipient_type', 30)->comment('CUSTOMER, SUPPLIER, EMPLOYEE, GOVERNMENT, INTERNAL_DEPARTMENT, OTHER');
            $table->string('recipient_name', 200)->nullable();
            $table->string('recipient_company', 200)->nullable();
            $table->text('recipient_address')->nullable();
            $table->string('recipient_phone', 50)->nullable();
            $table->string('recipient_email', 150)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, NUMBER_RESERVED, REVIEW, APPROVED, SIGNED, SENT, ARCHIVED, PUBLISHED, REJECTED, CANCELLED, VOID');
            $table->string('attachment_path', 255)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'letter_date']);
        });

        Schema::create('letter_number_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('doc', 20)->default('LETTER');
            $table->string('number', 100)->unique();
            $table->string('status', 20)->default('RESERVED')->comment('RESERVED, USED, CANCELLED, VOID');
            $table->foreignId('letter_register_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['doc', 'status']);
        });

        // ---- Receipts (kwitansi, always linked to a real payment) ----
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100)->unique();
            $table->date('receipt_date');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payer_name', 200);
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('payment_method', 30)->default('TRANSFER');
            $table->foreignId('cash_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_no', 100)->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, ISSUED, CONFIRMED, PAID, VOID');
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'receipt_date']);
        });

        // ---- Sparepart storage locations (warehouse → zone → rack → bin) ----
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('zone', 20)->default('A');
            $table->string('rack', 20)->default('A01');
            $table->string('bin', 20)->default('A01-01');
            $table->string('code', 50);
            $table->string('name', 150)->nullable();
            $table->decimal('capacity', 20, 4)->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });

        // ---- Sparepart ↔ equipment compatibility ----
        Schema::create('sparepart_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('equipment_make', 100)->nullable();
            $table->string('equipment_model', 100)->nullable();
            $table->string('part_number', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ---- Legacy import batches ----
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->comment('sparepart_master, opening_stock, letter_register, legacy_invoice, legacy_receipt');
            $table->string('file_name', 255);
            $table->string('status', 20)->default('UPLOADED')->comment('UPLOADED, MAPPED, VALIDATED, IMPORTED, FAILED');
            $table->json('column_map')->nullable();
            $table->json('preview')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->string('error_report_path', 255)->nullable();
            $table->boolean('post_accounting')->default(false);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        // ---- Extend items for sparepart administration ----
        Schema::table('items', function (Blueprint $table) {
            $table->string('brand', 100)->nullable()->after('name');
            $table->string('part_number', 100)->nullable()->after('brand');
            $table->string('alt_part_number', 100)->nullable()->after('part_number');
            $table->foreignId('storage_location_id')->nullable()->after('reorder_point')->constrained()->nullOnDelete();
            $table->decimal('max_stock', 20, 4)->nullable()->after('reorder_point');
            $table->decimal('last_purchase_price', 20, 2)->nullable();
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('source', 20)->default('NATIVE')->comment('NATIVE, LEGACY_IMPORT');
            $table->string('legacy_reference', 100)->nullable();
        });

        // ---- Stock reservations: track reserve → issue → return ----
        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->decimal('issued_qty', 20, 4)->default(0)->after('qty');
            $table->decimal('returned_qty', 20, 4)->default(0)->after('issued_qty');
        });

        // ---- Stock ledger: legacy flag + storage location ----
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->string('source', 20)->default('NATIVE')->comment('NATIVE, LEGACY_IMPORT');
            $table->string('import_batch_id', 50)->nullable();
            $table->foreignId('storage_location_id')->nullable()->constrained()->nullOnDelete();
        });

        // ---- Invoices/receipts/letters legacy flags ----
        foreach (['invoices', 'receipts', 'letter_registers'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->string('source', 20)->default('NATIVE')->comment('NATIVE, LEGACY_IMPORT');
                $table->string('legacy_reference', 100)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['invoices', 'receipts', 'letter_registers'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropColumn(['source', 'legacy_reference']);
            });
        }
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->dropColumn(['source', 'import_batch_id', 'storage_location_id']);
        });
        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->dropColumn(['issued_qty', 'returned_qty']);
        });
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['brand', 'part_number', 'alt_part_number', 'storage_location_id', 'max_stock', 'last_purchase_price', 'preferred_supplier_id', 'source', 'legacy_reference']);
        });
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('sparepart_compatibilities');
        Schema::dropIfExists('storage_locations');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('letter_number_reservations');
        Schema::dropIfExists('letter_registers');
        Schema::dropIfExists('letter_types');
    }
};
