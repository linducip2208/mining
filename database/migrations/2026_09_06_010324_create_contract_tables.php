<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete()->comment('product');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('contract_qty', 20, 4)->default(0);
            $table->decimal('price', 20, 2)->default(0);
            $table->text('pricing_formula')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_term', 100)->nullable();
            $table->foreignId('product_specification_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_code', 20)->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, ACTIVE, COMPLETED, EXPIRED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'item_id', 'status']);
        });

        Schema::create('supplier_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_description', 255)->nullable();
            $table->decimal('contract_qty', 20, 4)->nullable();
            $table->decimal('contract_value', 20, 2)->nullable();
            $table->decimal('price', 20, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            $table->text('sla')->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, ACTIVE, COMPLETED, EXPIRED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('hauling_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete()->comment('vendor');
            $table->foreignId('hauling_route_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rate_type', 20)->default('PER_TON')->comment('PER_TON, PER_KM, PER_TRIP');
            $table->decimal('rate', 20, 2)->default(0);
            $table->decimal('minimum_volume', 20, 4)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, ACTIVE, COMPLETED, EXPIRED, CANCELLED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hauling_contracts');
        Schema::dropIfExists('supplier_contracts');
        Schema::dropIfExists('customer_contracts');
    }
};
