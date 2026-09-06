<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('role', 50)->nullable()->after('user_id')
                ->comment('primary role code at action time');
            $table->foreignId('company_id')->nullable()->after('role')
                ->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->after('company_id')
                ->constrained()->nullOnDelete();
            $table->string('device', 100)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('site_id');
            $table->dropColumn(['role', 'device']);
        });
    }
};
