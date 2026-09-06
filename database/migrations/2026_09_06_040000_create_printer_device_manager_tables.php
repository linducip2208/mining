<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_devices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('printer_type', 30)->default('A4');
            $table->string('connection_type', 30)->default('SYSTEM');
            $table->string('device_identifier', 255)->nullable();
            $table->string('paper_size', 20)->default('A4');
            $table->json('document_types')->nullable();
            $table->string('workstation', 150)->nullable();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_print')->default(false);
            $table->unsignedTinyInteger('copies')->default(1);
            $table->string('status', 20)->default('UNKNOWN');
            $table->timestamp('last_seen_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['connection_type', 'status']);
            $table->index(['site_id', 'company_id', 'workstation']);
        });

        Schema::create('print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('idempotency_key', 150)->unique();
            $table->string('document_type', 50);
            $table->unsignedBigInteger('document_id');
            $table->foreignId('printer_device_id')->nullable()->constrained('printer_devices')->nullOnDelete();
            $table->string('workstation', 150)->nullable();
            $table->unsignedTinyInteger('copies')->default(1);
            $table->string('status', 20)->default('QUEUED');
            $table->json('payload')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['document_type', 'document_id']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
        Schema::dropIfExists('printer_devices');
    }
};
