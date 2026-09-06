<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_tank_dips', function (Blueprint $table) {
            $table->decimal('variance_pct', 8, 3)->default(0)->after('variance')
                ->comment('variance / system * 100');
            $table->string('status', 20)->default('NORMAL')->after('variance_pct')
                ->comment('NORMAL, PENDING (over threshold, butuh approve), APPROVED');
            $table->foreignId('approved_by')->nullable()->after('measured_by')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fuel_tank_dips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['variance_pct', 'status']);
        });
    }
};
