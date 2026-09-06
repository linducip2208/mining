<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telematics_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('driver', 50)->comment('class short name: Simulator, RestApi, ...');
            $table->json('config')->nullable()->comment('endpoint, api key ref, poll interval');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Normalized external events (single source regardless of provider)
        Schema::create('telematics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telematics_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_unit_id', 100)->nullable()->index();
            $table->string('event_type', 30)->index()->comment('LOCATION, SPEED, IGNITION, ENGINE_HOUR, ODOMETER, FUEL_LEVEL, GEOFENCE, TRIP, IDLE');
            $table->dateTime('event_time')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('speed_kph', 8, 2)->nullable();
            $table->boolean('ignition_on')->nullable();
            $table->decimal('engine_hour', 12, 1)->nullable();
            $table->decimal('odometer_km', 12, 1)->nullable();
            $table->decimal('fuel_percent', 5, 2)->nullable();
            $table->string('geofence', 100)->nullable();
            $table->decimal('idle_minutes', 8, 2)->nullable();
            $table->json('raw')->nullable()->comment('original provider payload');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['equipment_id', 'event_time']);
        });

        Schema::create('ai_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('LOCAL')->comment('LOCAL, OPENAI, ANTHROPIC, GEMINI, DEEPSEEK, GLM, OPENROUTER, OLLAMA');
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->json('data_sources')->nullable()->comment('which queries/services were used');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audit_logs');
        Schema::dropIfExists('telematics_events');
        Schema::dropIfExists('telematics_providers');
    }
};
