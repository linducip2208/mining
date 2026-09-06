<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loading_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'code']);
        });

        Schema::create('dumping_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('type', 30)->default('STOCKPILE')->comment('STOCKPILE, CRUSHER, PORT, WASTE_DUMP');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crusher_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'code']);
        });

        Schema::create('hauling_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->foreignId('loading_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dumping_point_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'code']);
        });

        Schema::create('dispatch_trips', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->constrained();
            $table->date('trip_date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('loader_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('pit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loading_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dumping_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hauling_route_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('loading_start')->nullable();
            $table->dateTime('loading_finish')->nullable();
            $table->dateTime('dump_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->decimal('tonnage', 20, 4)->default(0)->comment('from weighbridge — never input manually when linked');
            $table->foreignId('weighbridge_ticket_id')->nullable()->unique();
            $table->string('status', 20)->default('PLANNED')->comment('PLANNED, LOADING, HAULING, DUMPED, COMPLETED, CANCELLED');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['trip_date', 'status']);
            $table->index(['truck_id', 'trip_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_trips');
        Schema::dropIfExists('hauling_routes');
        Schema::dropIfExists('dumping_points');
        Schema::dropIfExists('loading_points');
    }
};
