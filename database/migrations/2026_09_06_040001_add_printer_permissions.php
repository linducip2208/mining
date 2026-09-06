<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['printer.view', 'Printer & Perangkat - Lihat', 'printer', 'view'],
            ['printer.manage', 'Printer & Perangkat - Kelola', 'printer', 'manage'],
            ['printer.test', 'Printer & Perangkat - Test Print', 'printer', 'test'],
            ['document.reprint', 'Dokumen - Cetak Ulang', 'document', 'reprint'],
            ['weighbridge.reprint', 'Timbangan - Cetak Ulang', 'weighbridge', 'reprint'],
        ] as [$code, $name, $module, $group]) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'module' => $module, 'group' => $group, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('code', [
            'printer.view', 'printer.manage', 'printer.test', 'document.reprint', 'weighbridge.reprint',
        ])->delete();
    }
};
