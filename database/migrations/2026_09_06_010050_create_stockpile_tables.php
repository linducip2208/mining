<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stockpiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete()->comment('product/material stored');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete()->comment('linked warehouse if any');
            $table->decimal('capacity_ton', 14, 2)->default(0);
            $table->decimal('survey_threshold_pct', 5, 2)->default(3)->comment('variance tolerance %');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'code']);
        });

        // Append-only stockpile movement ledger
        Schema::create('stockpile_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stockpile_id')->constrained()->cascadeOnDelete();
            $table->date('trx_date');
            $table->string('movement_type', 30)->index()->comment('OPENING, PRODUCTION_IN, TRANSFER_IN, TRANSFER_OUT, SALES_OUT, ADJUSTMENT_PLUS, ADJUSTMENT_MINUS');
            $table->decimal('qty_in', 20, 4)->default(0);
            $table->decimal('qty_out', 20, 4)->default(0);
            $table->string('ref_type', 30)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['stockpile_id', 'trx_date'], 'spm_pile_date');
        });

        Schema::create('stockpile_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stockpile_id')->constrained()->cascadeOnDelete();
            $table->date('survey_date');
            $table->decimal('survey_balance', 20, 4)->default(0);
            $table->decimal('system_balance', 20, 4)->default(0)->comment('snapshot at survey time');
            $table->decimal('variance', 20, 4)->default(0);
            $table->decimal('variance_pct', 8, 3)->default(0);
            $table->string('status', 20)->default('PENDING')->comment('PENDING, APPROVED, INVESTIGATE, CLOSED');
            $table->text('investigation')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('surveyor', 150)->nullable();
            $table->string('attachment', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['stockpile_id', 'survey_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stockpile_surveys');
        Schema::dropIfExists('stockpile_movements');
        Schema::dropIfExists('stockpiles');
    }
};
