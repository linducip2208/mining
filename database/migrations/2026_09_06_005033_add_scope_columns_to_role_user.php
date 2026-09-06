<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('division_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('division_id');
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
