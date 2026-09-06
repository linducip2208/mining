<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('serial_no', 50)->unique();
            $table->string('brand', 100)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('pattern', 50)->nullable();
            $table->decimal('purchase_cost', 20, 2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->string('status', 20)->default('NEW')->comment('NEW, INSTALLED, REPAIR, STOCK, SCRAP');
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete()->comment('current installation');
            $table->string('position', 30)->nullable()->comment('e.g. FL, FR, R1L, R2R');
            $table->date('install_date')->nullable();
            $table->decimal('install_hm', 12, 1)->default(0);
            $table->decimal('install_km', 12, 1)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'equipment_id']);
        });

        Schema::create('tire_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tire_id')->constrained()->cascadeOnDelete();
            $table->date('trx_date');
            $table->string('movement_type', 20)->index()->comment('INSTALL, REMOVE, ROTATE, INSPECT, REPAIR, SCRAP');
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position', 30)->nullable();
            $table->decimal('hm_reading', 12, 1)->default(0);
            $table->decimal('km_reading', 12, 1)->default(0);
            $table->decimal('tread_depth', 6, 2)->nullable()->comment('mm, for inspection');
            $table->decimal('cost', 20, 2)->default(0)->comment('repair cost if any');
            $table->string('reason', 255)->nullable()->comment('removal reason');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['tire_id', 'trx_date']);
        });

        Schema::create('tire_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->date('inspection_date');
            $table->foreignId('inspector_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->json('details')->nullable()->comment('per-position tread/pressure/condition');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tire_inspections');
        Schema::dropIfExists('tire_movements');
        Schema::dropIfExists('tires');
    }
};
