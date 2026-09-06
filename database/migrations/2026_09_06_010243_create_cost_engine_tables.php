<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Manual cost components without a dedicated source module
        // (contractor, royalty, overhead, other). Everything else is
        // computed live from real transactions by CostEngine.
        Schema::create('mining_other_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 7)->comment('YYYY-MM');
            $table->string('component', 30)->comment('CONTRACTOR, ROYALTY, OVERHEAD, HAULING, CRUSHER, OTHER');
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, APPROVED, POSTED');
            $table->foreignId('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'site_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mining_other_costs');
    }
};
