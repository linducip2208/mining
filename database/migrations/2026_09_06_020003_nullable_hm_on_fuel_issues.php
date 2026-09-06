<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_issues', function (Blueprint $table) {
            $table->decimal('hm_before', 12, 2)->nullable()->change();
            $table->decimal('hm_after', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fuel_issues', function (Blueprint $table) {
            $table->decimal('hm_before', 12, 2)->default(0)->change();
            $table->decimal('hm_after', 12, 2)->default(0)->change();
        });
    }
};
