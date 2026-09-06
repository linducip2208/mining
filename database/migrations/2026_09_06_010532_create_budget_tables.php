<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('type', 10)->default('OPEX')->comment('OPEX, CAPEX');
            $table->unsignedTinyInteger('version')->default(1);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, APPROVED, REVISED, CLOSED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'year', 'status']);
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 7)->nullable()->comment('YYYY-MM, null = annual pool');
            $table->decimal('amount', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['budget_id', 'chart_of_account_id']);
        });

        // Commitments from PR/PO (released when consumed by actuals)
        Schema::create('budget_commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ref_type', 30)->comment('PURCHASE_REQUEST, PURCHASE_ORDER');
            $table->unsignedBigInteger('ref_id');
            $table->string('ref_number', 50)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('status', 20)->default('COMMITTED')->comment('COMMITTED, RELEASED, CONSUMED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['ref_type', 'ref_id'], 'bc_ref_unique');
            $table->index(['budget_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_commitments');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};
