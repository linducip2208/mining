<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hse_reports', function (Blueprint $table) {
            $table->foreignId('investigated_by')->nullable()->after('reported_by')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('investigated_at')->nullable()->after('investigated_by');
        });
    }

    public function down(): void
    {
        Schema::table('hse_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investigated_by');
            $table->dropColumn('investigated_at');
        });
    }
};
