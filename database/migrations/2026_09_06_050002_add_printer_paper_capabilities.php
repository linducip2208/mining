<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printer_devices', function (Blueprint $table): void {
            $table->boolean('supports_auto_cut')->default(false)->after('auto_print');
            $table->unsignedSmallInteger('character_width')->nullable()->after('supports_auto_cut');
        });
    }

    public function down(): void
    {
        Schema::table('printer_devices', function (Blueprint $table): void {
            $table->dropColumn(['supports_auto_cut', 'character_width']);
        });
    }
};
