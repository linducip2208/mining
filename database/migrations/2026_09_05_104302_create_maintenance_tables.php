<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('asset_category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->string('type', 30)->default('EQUIPMENT')->comment('EQUIPMENT, VEHICLE, MACHINE, CRUSHER, BUILDING, LAND, OTHER');
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_no', 100)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 20, 2)->default(0);
            $table->decimal('accumulated_depreciation', 20, 2)->default(0);
            $table->decimal('depreciation_method', 20, 0)->default(0)->comment('unused, kept for compat');
            $table->unsignedTinyInteger('useful_life_years')->default(0);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete()->comment('responsible');
            $table->decimal('meter_reading', 12, 1)->default(0);
            $table->string('status', 20)->default('AVAILABLE')->comment('AVAILABLE, IN_USE, MAINTENANCE, BREAKDOWN, RETIRED, DISPOSED');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('PREVENTIVE')->comment('PREVENTIVE, CORRECTIVE');
            $table->string('name', 150);
            $table->string('interval_type', 20)->default('RUNNING_HOUR')->comment('RUNNING_HOUR, KM, DAY, MONTH');
            $table->unsignedInteger('interval_value')->default(0);
            $table->unsignedInteger('last_meter')->default(0);
            $table->date('last_done')->nullable();
            $table->date('next_due')->nullable();
            $table->unsignedInteger('next_meter')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('priority', 20)->default('NORMAL')->comment('LOW, NORMAL, HIGH, URGENT');
            $table->text('problem')->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->string('status', 20)->default('OPEN')->comment('OPEN, CONVERTED, CANCELLED');
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('maintenance_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('maintenance_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('PREVENTIVE')->comment('PREVENTIVE, CORRECTIVE, BREAKDOWN');
            $table->string('priority', 20)->default('NORMAL');
            $table->date('date');
            $table->date('planned_finish')->nullable();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_finish')->nullable();
            $table->text('description')->nullable();
            $table->decimal('estimated_cost', 20, 2)->default(0);
            $table->decimal('actual_cost', 20, 2)->default(0);
            $table->decimal('downtime_hours', 8, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, SUBMITTED, APPROVED, IN_PROGRESS, COMPLETED, CLOSED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'date']);
        });

        Schema::create('work_order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255);
            $table->boolean('is_done')->default(false);
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('technician_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('hours', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('maintenance_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 2)->default(0);
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->string('issue_status', 20)->default('PENDING')->comment('PENDING, ISSUED');
            $table->foreignId('stock_ledger_id')->nullable();
            $table->timestamps();
        });

        Schema::create('downtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->decimal('hours', 8, 2)->default(0);
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('cost_type', 30)->comment('PART, LABOR, SERVICE, OTHER');
            $table->decimal('amount', 20, 2)->default(0);
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ============ FINANCE & ACCOUNTING ============
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete()->comment('null = global/standard');
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('type', 20)->comment('ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE');
            $table->string('subtype', 50)->nullable()->comment('CASH, BANK, AR, AP, INVENTORY, TAX, SALARY, etc');
            $table->boolean('is_postable')->default(true)->comment('false = header account');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('type');
        });

        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('type', 20)->default('BANK')->comment('CASH, BANK');
            $table->string('bank_name', 100)->nullable();
            $table->string('account_no', 50)->nullable();
            $table->foreignId('coa_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('period', 7)->comment('YYYY-MM');
            $table->string('status', 20)->default('OPEN')->comment('OPEN, CLOSING, CLOSED');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'period']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->date('journal_date');
            $table->string('period', 7);
            $table->string('source_type', 30)->nullable()->comment('SALES_INVOICE, PAYMENT, PAYROLL, GRN, etc');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_number', 50)->nullable();
            $table->string('memo', 255)->nullable();
            $table->decimal('total_debit', 20, 2)->default(0);
            $table->decimal('total_credit', 20, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, POSTED, VOID');
            $table->boolean('is_reversal')->default(false);
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['journal_date', 'status']);
            $table->index(['source_type', 'source_id'], 'je_source');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('memo', 255)->nullable();
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->timestamps();
            $table->index('chart_of_account_id', 'jl_coa');
        });

        Schema::create('accounting_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('e.g. SALES_REVENUE, AR_TRADE, TAX_PPN_OUT, INVENTORY_FG');
            $table->string('name', 150);
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->string('module', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('type', 30)->comment('PPN_OUT, PPN_IN, PBB, PPH21, PPH23, OTHER');
            $table->decimal('rate', 6, 2)->default(0)->comment('percent, configurable');
            $table->foreignId('sales_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('tax_code_id')->constrained()->restrictOnDelete();
            $table->string('transaction_type', 30)->comment('SALES, PURCHASE, OTHER');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->date('trx_date');
            $table->string('period', 7);
            $table->string('tax_invoice_no', 100)->nullable()->comment('no faktur pajak');
            $table->decimal('tax_base', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->string('kind', 20)->default('PPN')->comment('PPN, PBB, SPT, OTHER');
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE, SETTLED, VOID');
            $table->timestamps();
            $table->index(['period', 'kind', 'status'], 'tx_period_kind');
        });

        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);
            $table->decimal('book_balance', 20, 2)->default(0);
            $table->decimal('bank_balance', 20, 2)->default(0);
            $table->decimal('difference', 20, 2)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['cash_account_id', 'period']);
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained()->cascadeOnDelete();
            $table->date('trx_date');
            $table->string('type', 20)->comment('IN, OUT');
            $table->string('ref_type', 30)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->boolean('reconciled')->default(false);
            $table->timestamps();
            $table->index(['cash_account_id', 'trx_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('reconciliations');
        Schema::dropIfExists('tax_transactions');
        Schema::dropIfExists('tax_codes');
        Schema::dropIfExists('accounting_mappings');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('cash_accounts');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('maintenance_costs');
        Schema::dropIfExists('downtimes');
        Schema::dropIfExists('maintenance_parts');
        Schema::dropIfExists('technician_assignments');
        Schema::dropIfExists('work_order_tasks');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('maintenance_requests');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('assets');
    }
};
