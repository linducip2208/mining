<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('scope_type', 20)->default('GLOBAL')->after('key');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
        });

        // The original key-only unique index prevented future COMPANY/SITE
        // overrides. Preserve existing rows as GLOBAL while allowing scoped rows.
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique('settings_key_unique');
            $table->unique(['scope_type', 'scope_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique('settings_scope_type_scope_id_key_unique');
            $table->dropColumn(['scope_type', 'scope_id']);
            $table->unique('key');
        });
    }
};
