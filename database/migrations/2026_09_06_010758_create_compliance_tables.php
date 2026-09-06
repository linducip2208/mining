<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_registers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->string('type', 20)->index()->comment('PERMIT, LICENSE, EMP_CERT, EQUIP_CERT, ENVIRONMENT, CONTRACT, OTHER');
            $table->string('title', 255);
            $table->string('document_number', 100)->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete()->comment('owner: employee cert');
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete()->comment('owner: equipment cert');
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete()->comment('linked archive');
            $table->date('issued_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE, EXPIRING_SOON, EXPIRED, RENEWED, CANCELLED');
            $table->string('attachment', 255)->nullable();
            $table->json('reminder_days')->nullable()->comment('default [90,60,30,14,7]');
            $table->date('last_reminded_at')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_registers');
    }
};
