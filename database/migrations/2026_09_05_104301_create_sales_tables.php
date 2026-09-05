<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->foreignId('price_list_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, SUBMITTED, APPROVED, PARTIALLY_DELIVERED, COMPLETED, CANCELLED, REJECTED');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'order_date']);
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('qty', 20, 4)->default(0);
            $table->decimal('qty_delivered', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('total_price', 20, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->date('delivery_date');
            $table->foreignId('vehicle_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('vehicle_plate', 30)->nullable();
            $table->string('driver_name', 150)->nullable();
            $table->decimal('total_qty', 20, 4)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, LOADING, COMPLETED, CANCELLED');
            $table->foreignId('weighbridge_ticket_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'delivery_date']);
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_ordered', 20, 4)->default(0);
            $table->decimal('qty_delivered', 20, 4)->default(0)->comment('final, from weighbridge net');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_code', 20)->nullable();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, POSTED, PARTIALLY_PAID, PAID, CANCELLED, VOID');
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'invoice_date']);
            $table->index(['customer_id', 'status'], 'inv_customer_status');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('qty', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('total_price', 20, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->string('type', 20)->comment('RECEIVE, PAY');
            $table->foreignId('company_id')->constrained();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payment_date');
            $table->string('method', 30)->default('CASH')->comment('CASH, BANK_TRANSFER, CHECK, GIRO, VIRTUAL_ACCOUNT');
            $table->foreignId('cash_account_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, POSTED, CANCELLED');
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'status', 'payment_date']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_type', 20)->comment('CUSTOMER, VENDOR');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 20, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('customer_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('deposit_date');
            $table->string('movement_type', 20)->index()->comment('DEPOSIT_IN, DEPOSIT_USED, DEPOSIT_REFUND, DEPOSIT_ADJUSTMENT');
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('ref_type', 30)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_number', 50)->nullable();
            $table->foreignId('cash_account_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['customer_id', 'deposit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_deposits');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
