<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['report.pdf', 'Laporan - Download PDF', 'report', 'pdf'],
            ['invoice.pdf', 'Faktur - Download PDF', 'invoice', 'pdf'],
            ['payroll.pdf', 'Payroll - Download PDF', 'payroll', 'pdf'],
            ['document.pdf', 'Dokumen - Download PDF', 'document', 'pdf'],
        ] as [$code, $name, $module, $group]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], ['name' => $name, 'module' => $module, 'group' => $group, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('code', ['report.pdf', 'invoice.pdf', 'payroll.pdf', 'document.pdf'])->delete();
    }
};
