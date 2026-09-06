<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend equipment as the fleet unit master (no duplicate table)
        Schema::table('equipment', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('model');
            }
            if (!Schema::hasColumn('equipment', 'fuel_type')) {
                $table->string('fuel_type', 20)->default('SOLAR')->after('ownership')->comment('SOLAR, BENSIN, LISTRIK');
            }
            if (!Schema::hasColumn('equipment', 'capacity_ton')) {
                $table->decimal('capacity_ton', 10, 2)->default(0)->after('fuel_type');
            }
            if (!Schema::hasColumn('equipment', 'purchase_date')) {
                $table->date('purchase_date')->nullable()->after('capacity_ton');
            }
            if (!Schema::hasColumn('equipment', 'purchase_cost')) {
                $table->decimal('purchase_cost', 20, 2)->default(0)->after('purchase_date');
            }
            if (!Schema::hasColumn('equipment', 'useful_life_years')) {
                $table->unsignedTinyInteger('useful_life_years')->default(5)->after('purchase_cost');
            }
            if (!Schema::hasColumn('equipment', 'operator_id')) {
                $table->foreignId('operator_id')->nullable()->after('useful_life_years')->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('equipment', 'odometer_km')) {
                $table->decimal('odometer_km', 12, 1)->default(0)->after('meter_reading')->comment('kilometer for wheeled units');
            }
        });

        if (!Schema::hasTable('equipment_categories')) {
            Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('type', 30)->default('HEAVY')->comment('HEAVY, VEHICLE, SUPPORT');
            $table->decimal('standard_fuel_lph', 8, 2)->default(0)->comment('standard liter per hour');
            $table->decimal('fuel_warning_pct', 5, 2)->default(20)->comment('variance threshold %');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // Hour meter / odometer reading history (append-only)
        if (!Schema::hasTable('equipment_meter_logs')) {
        Schema::create('equipment_meter_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('hm_start', 12, 1)->default(0);
            $table->decimal('hm_end', 12, 1)->default(0);
            $table->decimal('km_start', 12, 1)->default(0);
            $table->decimal('km_end', 12, 1)->default(0);
            $table->decimal('operating_hours', 8, 2)->default(0);
            $table->decimal('idle_hours', 8, 2)->default(0);
            $table->string('status', 20)->default('IN_USE')->comment('snapshot of unit status');
            $table->foreignId('operator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['equipment_id', 'log_date']);
        });
        }

        // Daily inspection checklist results
        if (!Schema::hasTable('equipment_inspections')) {
        Schema::create('equipment_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->date('inspection_date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->json('checklist')->nullable()->comment('item => OK/NOT_OK + notes');
            $table->string('result', 20)->default('PASS')->comment('PASS, FAIL, CONDITIONAL');
            $table->text('findings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['equipment_id', 'inspection_date']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspections');
        Schema::dropIfExists('equipment_meter_logs');
        Schema::dropIfExists('equipment_categories');
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipment_category_id');
            $table->dropConstrainedForeignId('operator_id');
            $table->dropColumn(['year', 'fuel_type', 'capacity_ton', 'purchase_date', 'purchase_cost', 'useful_life_years', 'odometer_km']);
        });
    }
};
