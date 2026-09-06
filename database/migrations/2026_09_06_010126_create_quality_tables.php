<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('unit', 20)->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE, EXPIRED, CANCELLED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['item_id', 'customer_id', 'status']);
        });

        Schema::create('product_spec_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_specification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quality_parameter_id')->constrained()->restrictOnDelete();
            $table->decimal('min_value', 14, 4)->nullable();
            $table->decimal('max_value', 14, 4)->nullable();
            $table->decimal('target_value', 14, 4)->nullable();
            $table->timestamps();
            $table->unique(['product_specification_id', 'quality_parameter_id'], 'psl_spec_param');
        });

        Schema::create('qc_samples', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type', 30)->comment('PRODUCTION_BATCH, STOCKPILE, DELIVERY_ORDER, SALES_ORDER');
            $table->unsignedBigInteger('source_id');
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('sample_date');
            $table->string('status', 20)->default('PENDING')->comment('PENDING, PASS, HOLD, REJECT');
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tested_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('qc_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_sample_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quality_parameter_id')->constrained()->restrictOnDelete();
            $table->decimal('result_value', 14, 4);
            $table->string('result', 10)->default('PENDING')->comment('PASS, FAIL, PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('coa_documents', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('qc_sample_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('issue_date');
            $table->json('results')->nullable()->comment('parameter snapshot');
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, ISSUED, CANCELLED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('quality_holds', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('qc_sample_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('HOLD')->comment('HOLD, RELEASED, CANCELLED');
            $table->foreignId('special_approval_by')->nullable()->constrained('users')->nullOnDelete()->comment('special approval bypasses block');
            $table->text('special_approval_note')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'delivery_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_holds');
        Schema::dropIfExists('coa_documents');
        Schema::dropIfExists('qc_tests');
        Schema::dropIfExists('qc_samples');
        Schema::dropIfExists('product_spec_lines');
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('quality_parameters');
    }
};
