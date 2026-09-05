<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 100)->unique()->comment('auto letter number e.g. 001/HR/IX/2026');
            $table->string('category', 30)->index()->comment('IN, OUT, INTERNAL, EMPLOYEE, VENDOR, LEGAL, PERMIT');
            $table->string('subject', 255);
            $table->text('body')->nullable();
            $table->date('date');
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('related_id')->nullable()->comment('employee/vendor id by category');
            $table->date('expiry_date')->nullable();
            $table->date('reminder_date')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedTinyInteger('version')->default(1);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, SUBMITTED, APPROVED, ARCHIVED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category', 'date']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('version');
            $table->string('file_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('letter_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('division_code', 10);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last_seq')->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'division_code', 'year']);
        });

        Schema::create('csr_programs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('budget', 20, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('PROPOSAL')->comment('PROPOSAL, APPROVED, IN_PROGRESS, COMPLETED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('csr_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csr_program_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->date('date');
            $table->decimal('estimated_cost', 20, 2)->default(0);
            $table->decimal('actual_cost', 20, 2)->default(0);
            $table->string('status', 20)->default('PLANNED')->comment('PLANNED, DONE, CANCELLED');
            $table->timestamps();
        });

        Schema::create('csr_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csr_activity_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->foreignId('cash_account_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->string('receipt_path', 255)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('csr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csr_program_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('file_path', 255)->nullable();
            $table->string('type', 30)->default('REPORT')->comment('PROPOSAL, BUDGET, REPORT, PHOTO, OTHER');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('event_type', 50)->index()->comment('MIN_STOCK, INVOICE_OVERDUE, MAINTENANCE_DUE, DOCUMENT_EXPIRY, PERMIT_EXPIRY, PRICE_VARIANCE, APPROVAL_PENDING');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('csr_documents');
        Schema::dropIfExists('csr_expenses');
        Schema::dropIfExists('csr_activities');
        Schema::dropIfExists('csr_programs');
        Schema::dropIfExists('letter_sequences');
        Schema::dropIfExists('document_downloads');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
