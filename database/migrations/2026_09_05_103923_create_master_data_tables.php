<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->unsignedTinyInteger('days')->default(0);
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('type', 20)->default('PRODUCT')->comment('PRODUCT, RAW, SPAREPART, CONSUMABLE, FUEL, GENERAL');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('item_category_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->default('PRODUCT')->comment('PRODUCT, RAW, SPAREPART, CONSUMABLE, FUEL, GENERAL');
            $table->foreignId('unit_id')->constrained();
            $table->text('description')->nullable();
            $table->decimal('min_stock', 20, 4)->default(0);
            $table->decimal('reorder_point', 20, 4)->default(0);
            $table->decimal('standard_cost', 20, 2)->default(0);
            $table->decimal('avg_cost', 20, 2)->default(0)->comment('moving average');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('type', 20)->default('WAREHOUSE')->comment('WAREHOUSE, STOCKPILE, CRUSHER_STOCK, FUEL_STATION');
            $table->text('address')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('pits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->decimal('area_hectare', 12, 2)->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'code']);
        });

        Schema::create('crushers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->decimal('capacity_ton_hour', 12, 2)->default(0);
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'code']);
        });

        Schema::create('weighbridges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->decimal('max_capacity', 12, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'code']);
        });

        Schema::create('weighbridge_calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weighbridge_id')->constrained()->cascadeOnDelete();
            $table->date('calibration_date');
            $table->string('version', 50)->nullable();
            $table->string('certificate_no', 100)->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('type', 30)->default('EXCAVATOR')->comment('EXCAVATOR, LOADER, DUMP_TRUCK, CRUSHER, DOZER, GRADER, DRILL, GENSET, OTHER');
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_no', 100)->nullable();
            $table->string('plate_no', 30)->nullable();
            $table->string('ownership', 20)->default('OWNED')->comment('OWNED, RENTED');
            $table->decimal('meter_reading', 12, 1)->default(0)->comment('hours/km');
            $table->string('status', 20)->default('AVAILABLE')->comment('AVAILABLE, IN_USE, MAINTENANCE, BREAKDOWN, RETIRED, DISPOSED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete()->comment('operator');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pit_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('working_hours', 8, 2)->default(0);
            $table->string('status', 20)->default('ASSIGNED')->comment('ASSIGNED, RELEASED');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('npwp', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('credit_limit', 20, 2)->default(0);
            $table->string('group', 50)->nullable()->comment('customer group for pricing');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('npwp', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('VENDOR')->comment('VENDOR, SERVICE, TRANSPORT');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('type', 30)->default('STANDARD')->comment('STANDARD, CUSTOMER, SITE, CONTRACT, RETAIL, SPECIAL');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('group', 50)->nullable()->comment('customer group scope');
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, APPROVED, ACTIVE, EXPIRED, CANCELLED');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 20, 2)->default(0);
            $table->decimal('min_qty', 20, 4)->default(0);
            $table->timestamps();
            $table->unique(['price_list_id', 'item_id']);
        });

        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('old_price', 20, 2)->default(0);
            $table->decimal('new_price', 20, 2)->default(0);
            $table->date('effective_date');
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('price_variances', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->string('module', 30);
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('reference_price', 20, 2)->default(0);
            $table->decimal('realization_price', 20, 2)->default(0);
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('variance', 20, 2)->default(0);
            $table->decimal('variance_percentage', 8, 2)->default(0);
            $table->string('variance_type', 20)->comment('FAVORABLE, UNFAVORABLE');
            $table->string('reason', 255)->nullable();
            $table->string('approval_status', 20)->default('PENDING');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_variances');
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('equipment_assignments');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('weighbridge_calibrations');
        Schema::dropIfExists('weighbridges');
        Schema::dropIfExists('crushers');
        Schema::dropIfExists('pits');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('units');
    }
};
