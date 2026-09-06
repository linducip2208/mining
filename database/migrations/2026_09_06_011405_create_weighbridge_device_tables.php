<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighbridge_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weighbridge_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('driver', 20)->default('MANUAL')->comment('MANUAL, SERIAL, TCP, REST');
            $table->json('config')->nullable()->comment('port, baud, host, endpoint, token ref');
            $table->string('api_token', 80)->nullable()->unique()->comment('for external REST ingest');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Raw device readings (stable + raw), operator-confirmed or not
        Schema::create('weighbridge_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weighbridge_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weighbridge_id')->constrained()->cascadeOnDelete();
            $table->dateTime('read_at')->index();
            $table->decimal('raw_weight', 20, 4)->default(0);
            $table->decimal('stable_weight', 20, 4)->default(0);
            $table->boolean('is_stable')->default(false);
            $table->boolean('is_manual')->default(true);
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('weighbridge_ticket_id')->nullable()->constrained()->nullOnDelete()->comment('consumed by ticket');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['weighbridge_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighbridge_readings');
        Schema::dropIfExists('weighbridge_devices');
    }
};
