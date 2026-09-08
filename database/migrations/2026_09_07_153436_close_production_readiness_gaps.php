<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Close remaining production readiness gaps:
     * - sparepart reserve → issue → return → consumed chain
     * - import workbook support (file hash, sheet, mode, force)
     * - import row fingerprint dedup
     * - payroll accounting split (BPJS / loan / other payable mappings)
     */
    public function up(): void
    {
        Schema::table('maintenance_parts', function (Blueprint $table) {
            $table->decimal('requested_qty', 20, 4)->default(0)->after('qty');
            $table->decimal('reserved_qty', 20, 4)->default(0)->after('requested_qty');
            $table->decimal('issued_qty', 20, 4)->default(0)->after('reserved_qty');
            $table->decimal('returned_qty', 20, 4)->default(0)->after('issued_qty');
            $table->decimal('consumed_qty', 20, 4)->default(0)->after('returned_qty');
        });

        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->decimal('consumed_qty', 20, 4)->default(0)->after('returned_qty');
            $table->decimal('issued_unit_cost', 20, 2)->nullable()->after('consumed_qty');
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('type')->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->string('file_hash', 64)->nullable()->after('file_name');
            $table->string('file_ext', 10)->default('csv')->after('file_hash');
            $table->string('sheet', 100)->nullable()->after('file_ext');
            $table->string('mode', 40)->nullable()->after('sheet')->comment('REGISTER_ONLY, OPENING_AR, HISTORY_ONLY, OPENING_PAYMENT, STOCK_ONLY, STOCK_AND_ACCOUNTING');
            $table->boolean('force_import')->default(false)->after('mode');
        });

        Schema::create('import_row_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('fingerprint', 64);
            $table->unsignedInteger('row_number')->default(0);
            $table->timestamps();
            $table->unique(['type', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_row_fingerprints');
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('site_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn(['file_hash', 'file_ext', 'sheet', 'mode', 'force_import']);
        });
        Schema::table('stock_reservations', function (Blueprint $table) {
            $table->dropColumn(['consumed_qty', 'issued_unit_cost']);
        });
        Schema::table('maintenance_parts', function (Blueprint $table) {
            $table->dropColumn(['requested_qty', 'reserved_qty', 'issued_qty', 'returned_qty', 'consumed_qty']);
        });
    }
};
