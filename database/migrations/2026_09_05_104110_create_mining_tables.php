<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mining_activities', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->constrained();
            $table->foreignId('pit_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type', 30)->default('MINING')->comment('MINING, HAULING, CLEANING, DRILLING, OTHER');
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete()->comment('material mined');
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('target_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('quantity', 20, 4)->default(0)->comment('trips/volume');
            $table->decimal('tonnage', 20, 4)->default(0);
            $table->decimal('working_hours', 8, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, SUBMITTED, APPROVED, POSTED, CANCELLED');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['date', 'site_id', 'status']);
        });

        Schema::create('haulings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mining_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('target_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('tonnage', 20, 4)->default(0);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('plate_no', 30);
            $table->string('name', 150);
            $table->string('type', 30)->default('DUMP_TRUCK')->comment('DUMP_TRUCK, TRAILER, PICKUP, BUS, OTHER');
            $table->decimal('capacity_ton', 10, 2)->default(0);
            $table->string('status', 20)->default('AVAILABLE');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('mining_productions', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->constrained();
            $table->foreignId('pit_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_tonnage', 20, 4)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('mining_production_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mining_production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mining_activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tonnage', 20, 4)->default(0);
            $table->timestamps();
        });

        // ============ WEIGHBRIDGE ============
        Schema::create('weighbridge_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 50)->unique();
            $table->foreignId('weighbridge_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->constrained();
            $table->string('direction', 20)->default('OUT')->comment('OUT = sales/loading, IN = receiving/unloading');
            $table->dateTime('first_weigh_at')->nullable();
            $table->dateTime('second_weigh_at')->nullable();
            $table->decimal('first_weight', 20, 4)->default(0);
            $table->decimal('second_weight', 20, 4)->default(0);
            $table->decimal('gross', 20, 4)->default(0);
            $table->decimal('tare', 20, 4)->default(0);
            $table->decimal('net', 20, 4)->default(0);
            $table->boolean('weight_overridden')->default(false);
            $table->text('override_reason')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('vehicle_plate', 30)->nullable();
            $table->string('driver_name', 150)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ref_type', 30)->nullable()->comment('DO, GRN, MINING, PRODUCTION');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_number', 50)->nullable();
            $table->string('calibration_version', 50)->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('FIRST_WEIGH')->comment('FIRST_WEIGH, COMPLETE, VALIDATED, POSTED, CANCELLED, VOID');
            $table->text('cancel_reason')->nullable();
            $table->unsignedTinyInteger('reprint_count')->default(0);
            $table->string('photo', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'wb_status_date');
            $table->index(['ref_type', 'ref_id']);
            $table->index('customer_id', 'wb_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighbridge_tickets');
        Schema::dropIfExists('mining_production_details');
        Schema::dropIfExists('mining_productions');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('haulings');
        Schema::dropIfExists('mining_activities');
    }
};
