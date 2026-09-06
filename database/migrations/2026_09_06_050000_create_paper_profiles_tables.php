<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 80)->unique();
            $table->string('paper_type', 20)->index();
            $table->decimal('width_mm', 8, 2);
            $table->decimal('height_mm', 8, 2)->nullable();
            $table->string('orientation', 20)->default('PORTRAIT');
            $table->decimal('margin_top_mm', 8, 2)->default(10);
            $table->decimal('margin_right_mm', 8, 2)->default(10);
            $table->decimal('margin_bottom_mm', 8, 2)->default(10);
            $table->decimal('margin_left_mm', 8, 2)->default(10);
            $table->boolean('is_continuous')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['paper_type', 'is_active']);
        });

        Schema::create('document_print_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 50)->index();
            $table->foreignId('printer_device_id')->nullable()->constrained('printer_devices')->nullOnDelete();
            $table->foreignId('paper_profile_id')->constrained('paper_profiles')->restrictOnDelete();
            $table->string('orientation', 20)->nullable();
            $table->unsignedTinyInteger('copies')->default(1);
            $table->boolean('auto_print')->default(false);
            $table->boolean('auto_cut')->default(false);
            $table->boolean('print_logo')->default(true);
            $table->boolean('print_qr')->default(false);
            $table->boolean('print_signature')->default(true);
            $table->boolean('print_watermark')->default(false);
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('workstation_key', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['document_type', 'company_id', 'site_id', 'workstation_key'], 'document_print_profiles_scope_index');
        });

        Schema::create('printer_paper_profile', function (Blueprint $table): void {
            $table->foreignId('printer_device_id')->constrained('printer_devices')->cascadeOnDelete();
            $table->foreignId('paper_profile_id')->constrained('paper_profiles')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->unique(['printer_device_id', 'paper_profile_id']);
        });

        Schema::create('print_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('workstation_key', 150)->nullable();
            $table->foreignId('last_printer_id')->nullable()->constrained('printer_devices')->nullOnDelete();
            $table->foreignId('last_paper_profile_id')->nullable()->constrained('paper_profiles')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'workstation_key'], 'print_preferences_user_workstation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_preferences');
        Schema::dropIfExists('printer_paper_profile');
        Schema::dropIfExists('document_print_profiles');
        Schema::dropIfExists('paper_profiles');
    }
};
