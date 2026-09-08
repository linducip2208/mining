<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real-file execution support: per-sheet layout snapshot + batch
     * close-out / sign-off for go-live acceptance (§88-§90).
     */
    public function up(): void
    {
        Schema::table('legacy_import_sheets', function (Blueprint $table) {
            $table->json('layout')->nullable()->after('target_module');
            $table->text('note')->nullable()->after('layout');
        });
        Schema::table('legacy_import_batches', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('notes');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->text('close_note')->nullable()->after('closed_by');
            $table->json('signoffs')->nullable()->after('close_note');
            $table->string('overall_status', 20)->default('')->after('signoffs');
        });
    }

    public function down(): void
    {
        Schema::table('legacy_import_batches', function (Blueprint $table) {
            $table->dropColumn(['closed_at', 'close_note', 'signoffs', 'overall_status']);
            $table->dropConstrainedForeignId('closed_by');
        });
        Schema::table('legacy_import_sheets', function (Blueprint $table) {
            $table->dropColumn(['layout', 'note']);
        });
    }
};
