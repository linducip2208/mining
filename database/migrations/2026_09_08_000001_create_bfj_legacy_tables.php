<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BFJ legacy migration staging layer (spec §1, §69-§72).
     * Raw workbook rows NEVER live in transactional tables; they stage here
     * until explicit IMPORT. Existing ledgers (StockLedger, CustomerDeposit,
     * JournalEntry, PayrollRun, Invoice, Receipt) remain single source of truth.
     */
    public function up(): void
    {
        Schema::create('bfj_import_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->json('config')->nullable()->comment('tolerance, cutoffs, payment/clearing mapping, overtime rules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bfj_column_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('bfj_import_profiles')->cascadeOnDelete();
            $table->string('target_module', 40);
            $table->string('legacy_header', 120);
            $table->string('target_field', 60);
            $table->timestamps();
            $table->unique(['profile_id', 'target_module', 'legacy_header'], 'bfj_alias_unique');
        });

        Schema::create('bfj_master_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 40)->comment('COMPANY,CUSTOMER,EMPLOYEE,VEHICLE,EQUIPMENT,MATERIAL,SPAREPART,COA,CASH_ACCOUNT,WAREHOUSE,LOCATION');
            $table->string('legacy_value', 160);
            $table->string('normalized_value', 160);
            $table->nullableMorphs('target');
            $table->string('status', 30)->default('POSSIBLE_MATCH')->comment('EXACT_MATCH,ALIAS_MATCH,POSSIBLE_MATCH,NEW_MASTER_REQUIRED,RESOLVED,IGNORED');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->timestamps();
            $table->index(['entity_type', 'normalized_value']);
        });

        Schema::create('legacy_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained('bfj_import_profiles')->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_hash', 64);
            $table->string('file_ext', 10)->default('xlsx');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mode', 40)->default('HISTORY_ONLY');
            $table->string('status', 20)->default('UPLOADED');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('allow_accounting_posting')->default(false);
            $table->boolean('allow_stock_posting')->default(false);
            $table->date('cutoff_date')->nullable();
            $table->json('cutoffs')->nullable()->comment('per-module cutoffs: sales,stock,accounting,payroll,deposit');
            $table->json('totals')->nullable();
            $table->json('reconciliation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['file_hash']);
            $table->index(['status']);
        });

        Schema::create('legacy_import_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_import_batches')->cascadeOnDelete();
            $table->string('sheet_name', 120);
            $table->string('detected_type', 40)->nullable();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->unsignedInteger('row_count')->default(0);
            $table->boolean('is_summary')->default(false)->comment('REKAP/SUMMARY never creates transactions (§64)');
            $table->string('action', 20)->default('IMPORT')->comment('IMPORT,RECONCILE_ONLY,IGNORE');
            $table->string('target_module', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('legacy_import_sheets')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('source')->nullable()->comment('raw cell values + formula + cached value');
            $table->json('normalized')->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->string('status', 20)->default('READY');
            $table->string('posting_effect', 20)->default('NONE')->comment('NONE,STOCK,ACCOUNTING,PAYROLL,DEPOSIT');
            $table->nullableMorphs('reference');
            $table->timestamps();
            $table->index(['fingerprint']);
            $table->index(['status']);
        });

        Schema::create('legacy_import_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_import_batches')->cascadeOnDelete();
            $table->foreignId('sheet_id')->nullable()->constrained('legacy_import_sheets')->nullOnDelete();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('code', 40)->comment('INVALID_DATE,FORMULA_ERROR,MISSING_SEQUENCE,NUMBER_PATTERN_VARIANCE,MISSING_FIELD,DUPLICATE_REFERENCE,ERROR_MISSING_QTY,AMOUNT_VARIANCE,...');
            $table->string('severity', 10)->default('WARNING');
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['batch_id', 'severity']);
        });

        Schema::create('legacy_import_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_import_batches')->cascadeOnDelete();
            $table->string('entity_type', 40);
            $table->string('legacy_value', 160);
            $table->string('normalized_value', 160)->nullable();
            $table->nullableMorphs('target');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->string('status', 30)->default('POSSIBLE_MATCH');
            $table->timestamps();
        });

        Schema::create('legacy_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_import_batches')->cascadeOnDelete();
            $table->string('scope', 30)->comment('SALES,DEPOSIT,FINANCE,PAYROLL,SPAREPART,DOCUMENTS,CROSS_FILE');
            $table->string('dimension', 120)->nullable()->comment('DATE|MATERIAL|CUSTOMER|ACCOUNT|... key');
            $table->decimal('legacy_total', 20, 2)->default(0);
            $table->decimal('erp_total', 20, 2)->default(0);
            $table->decimal('variance', 20, 2)->default(0);
            $table->string('status', 20)->default('UNREVIEWED')->comment('MATCH,VARIANCE,REVIEWED,FAIL');
            $table->text('root_cause')->nullable();
            $table->timestamps();
        });

        Schema::create('bfj_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('ledger', 20)->default('FINANCE')->comment('FINANCE,PAYROLL,SALES,SPAREPART');
            $table->string('legacy_category', 160);
            $table->foreignId('coa_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->string('flow_type', 20)->nullable()->comment('OPERATING,INVESTING,FINANCING,TRANSFER_INTERNAL,CLEARING,OPENING_BALANCE');
            $table->timestamps();
            $table->unique(['ledger', 'legacy_category'], 'bfj_cat_unique');
        });

        // Traceability link: every posted record keeps batch/file/sheet/row (§72).
        Schema::create('legacy_import_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_import_batches')->cascadeOnDelete();
            $table->morphs('reference');
            $table->string('source_sheet', 120)->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('legacy_reference', 160)->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->timestamps();
        });

        if (! Schema::hasColumn('stock_ledger', 'legacy_reference')) {
            Schema::table('stock_ledger', function (Blueprint $table) {
                $table->string('legacy_reference', 160)->nullable()->after('ref_number');
            });
        }

        DB::table('bfj_import_profiles')->insert([
            'code' => 'BFJ_LEGACY_2026',
            'name' => 'BFJ Legacy 2026 Migration Profile',
            'config' => json_encode([
                'amount_tolerance' => 1,
                'qty_tolerance' => 0.001,
                'sales_default_mode' => 'HISTORY_ONLY',
                'finance_default_mode' => 'RECONCILIATION_ONLY',
                'payroll_default_mode' => 'PAYROLL_RECONCILIATION',
                'payment_channels' => ['RITEL' => 'CASH', 'REKENING ALASEN' => 'PERSONAL_CLEARING', 'REKENING PERUSAHAAN' => 'COMPANY_BANK'],
                'clearing_account_code' => 'PERSONAL_CLEARING',
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('stock_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('stock_ledger', 'legacy_reference')) {
                $table->dropColumn('legacy_reference');
            }
        });
        Schema::dropIfExists('legacy_import_links');
        Schema::dropIfExists('bfj_category_mappings');
        Schema::dropIfExists('legacy_reconciliations');
        Schema::dropIfExists('legacy_import_matches');
        Schema::dropIfExists('legacy_import_issues');
        Schema::dropIfExists('legacy_import_rows');
        Schema::dropIfExists('legacy_import_sheets');
        Schema::dropIfExists('legacy_import_batches');
        Schema::dropIfExists('bfj_master_aliases');
        Schema::dropIfExists('bfj_column_aliases');
        Schema::dropIfExists('bfj_import_profiles');
    }
};
