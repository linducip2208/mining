<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_jobs', function (Blueprint $table): void {
            $table->foreignId('paper_profile_id')->nullable()->after('printer_device_id')->constrained('paper_profiles')->nullOnDelete();
            $table->json('paper_snapshot')->nullable()->after('payload');
            $table->json('printer_snapshot')->nullable()->after('paper_snapshot');
            $table->string('orientation_snapshot', 20)->nullable()->after('printer_snapshot');
            $table->decimal('paper_width_mm', 8, 2)->nullable()->after('orientation_snapshot');
            $table->decimal('paper_height_mm', 8, 2)->nullable()->after('paper_width_mm');
            $table->foreignId('reprint_of_id')->nullable()->after('paper_height_mm')->constrained('print_jobs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('print_jobs', function (Blueprint $table): void {
            $table->dropForeign(['reprint_of_id']);
            $table->dropForeign(['paper_profile_id']);
            $table->dropColumn(['paper_profile_id', 'paper_snapshot', 'printer_snapshot', 'orientation_snapshot', 'paper_width_mm', 'paper_height_mm', 'reprint_of_id']);
        });
    }
};
