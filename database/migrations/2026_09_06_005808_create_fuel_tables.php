<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_tanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->decimal('capacity_liter', 14, 2)->default(0);
            $table->string('fuel_type', 20)->default('SOLAR');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        // Append-only fuel movement ledger (source of truth — never edit balance directly)
        Schema::create('fuel_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fuel_tank_id')->constrained()->cascadeOnDelete();
            $table->date('trx_date');
            $table->string('movement_type', 30)->index()->comment('OPENING, RECEIPT, ISSUE, TRANSFER_IN, TRANSFER_OUT, ADJUSTMENT_PLUS, ADJUSTMENT_MINUS, RETURN');
            $table->decimal('qty_in', 14, 3)->default(0);
            $table->decimal('qty_out', 14, 3)->default(0);
            $table->decimal('unit_cost', 20, 2)->default(0);
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->string('ref_type', 30)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['fuel_tank_id', 'trx_date'], 'fl_tank_date');
        });

        Schema::create('fuel_issues', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->date('issue_date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fuel_tank_id')->constrained()->restrictOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_plate', 30)->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('hm_before', 12, 1)->default(0);
            $table->decimal('hm_after', 12, 1)->default(0);
            $table->decimal('liter', 14, 3)->default(0);
            $table->decimal('fuel_price', 20, 2)->default(0);
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->decimal('operating_hours', 8, 2)->default(0);
            $table->decimal('liter_per_hour', 10, 3)->default(0);
            $table->string('variance_status', 20)->default('NORMAL')->comment('NORMAL, WARNING, CRITICAL');
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, APPROVED, POSTED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['issue_date', 'status']);
        });

        Schema::create('fuel_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fuel_tank_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->date('receipt_date');
            $table->decimal('liter', 14, 3)->default(0);
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->string('delivery_note', 100)->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, POSTED, CANCELLED');
            $table->foreignId('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('from_tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->foreignId('to_tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->date('transfer_date');
            $table->decimal('liter', 14, 3)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, POSTED, CANCELLED');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_tank_dips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')->constrained()->cascadeOnDelete();
            $table->date('dip_date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('dip_cm', 8, 2)->default(0);
            $table->decimal('physical_liter', 14, 3)->default(0)->comment('converted physical stock');
            $table->decimal('system_liter', 14, 3)->default(0)->comment('ledger balance snapshot');
            $table->decimal('variance', 14, 3)->default(0);
            $table->foreignId('measured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['fuel_tank_id', 'dip_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_tank_dips');
        Schema::dropIfExists('fuel_transfers');
        Schema::dropIfExists('fuel_receipts');
        Schema::dropIfExists('fuel_issues');
        Schema::dropIfExists('fuel_ledger');
        Schema::dropIfExists('fuel_tanks');
    }
};
