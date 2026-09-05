<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->constrained();
            $table->foreignId('crusher_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('finish_time')->nullable();
            $table->decimal('input_tonnage', 20, 4)->default(0);
            $table->decimal('gross_output', 20, 4)->default(0);
            $table->decimal('total_loss', 20, 4)->default(0);
            $table->decimal('total_scrap', 20, 4)->default(0);
            $table->decimal('net_output', 20, 4)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, SUBMITTED, APPROVED, POSTED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['date', 'crusher_id', 'status']);
        });

        Schema::create('production_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tonnage', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('gross_tonnage', 20, 4)->default(0);
            $table->decimal('net_tonnage', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('production_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30)->comment('DEBU, MOISTURE, WASTE, PROCESS_LOSS, ADJUSTMENT');
            $table->decimal('tonnage', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('production_scraps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tonnage', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ============ INVENTORY ============
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->date('trx_date');
            $table->string('movement_type', 30)->index()->comment('OPENING, PURCHASE, PRODUCTION, SALE, TRANSFER_IN, TRANSFER_OUT, ISSUE, RETURN, ADJUSTMENT_PLUS, ADJUSTMENT_MINUS, SCRAP, MAINTENANCE_USAGE');
            $table->decimal('qty_in', 20, 4)->default(0);
            $table->decimal('qty_out', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 2)->default(0);
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->string('ref_type', 30)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['warehouse_id', 'item_id', 'trx_date'], 'sl_wh_item_date');
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->date('transfer_date');
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, SUBMITTED, APPROVED, POSTED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('qty', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->date('adjustment_date');
            $table->string('type', 20)->default('ADJUSTMENT')->comment('ADJUSTMENT, OPNAME');
            $table->string('status', 20)->default('DRAFT');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('system_qty', 20, 4)->default(0);
            $table->decimal('counted_qty', 20, 4)->default(0);
            $table->decimal('diff_qty', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->string('ref_type', 30);
            $table->unsignedBigInteger('ref_id');
            $table->string('ref_number', 50)->nullable();
            $table->decimal('qty', 20, 4)->default(0);
            $table->string('status', 20)->default('RESERVED')->comment('RESERVED, RELEASED, CONSUMED');
            $table->timestamps();
            $table->index(['warehouse_id', 'item_id', 'status'], 'sr_wh_item_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('production_scraps');
        Schema::dropIfExists('production_losses');
        Schema::dropIfExists('production_outputs');
        Schema::dropIfExists('production_inputs');
        Schema::dropIfExists('production_batches');
    }
};
