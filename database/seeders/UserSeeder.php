<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Production: demo users diblokir.');
            return;
        }
        if (User::where('username', 'gm')->exists()) {
            $this->command?->info('Demo users sudah ada — dilewati.');
            return;
        }

        // username, name, email, role, scope, company_code, site_code
        $users = [
            ['director', 'Ir. Hendra Wijaya', 'director@miningerp.local', 'DIRECTOR', 'ALL_COMPANIES', null, null],
            ['gm', 'Sari Kusuma', 'gm@miningerp.local', 'GENERAL_MANAGER', 'ALL_COMPANIES', null, null],
            ['mine_mgr', 'Bambang Sutrisno', 'mine.mgr@miningerp.local', 'MINE_MANAGER', 'COMPANY', 'MIN1', null],
            ['site_mgr', 'Agus Santoso', 'site.mgr@miningerp.local', 'SITE_MANAGER', 'SITE', 'MIN1', 'S-BJM'],
            ['prod_mgr', 'Dewi Lestari', 'prod.mgr@miningerp.local', 'PRODUCTION_MANAGER', 'COMPANY', 'MIN1', null],
            ['fin_mgr', 'Rina Marlina', 'fin.mgr@miningerp.local', 'FINANCE_MANAGER', 'COMPANY', 'MIN1', null],
            ['accounting', 'Eko Prasetyo', 'accounting@miningerp.local', 'ACCOUNTING', 'COMPANY', 'MIN1', null],
            ['hr_mgr', 'Nur Aisyah', 'hr.mgr@miningerp.local', 'HR_MANAGER', 'COMPANY', 'MIN1', null],
            ['purchasing', 'Joko Purwanto', 'purchasing@miningerp.local', 'PURCHASING', 'COMPANY', 'MIN1', null],
            ['wh_mgr', 'Tono Hidayat', 'wh.mgr@miningerp.local', 'WAREHOUSE_MANAGER', 'SITE', 'MIN1', 'S-KTN'],
            ['sales_mgr', 'Maya Putri', 'sales.mgr@miningerp.local', 'SALES_MANAGER', 'COMPANY', 'MIN1', null],
            ['sales', 'Fajar Nugroho', 'sales@miningerp.local', 'SALES', 'SITE', 'MIN1', 'S-BJM'],
            ['wb_opt', 'Slamet Riyadi', 'wb.op@miningerp.local', 'WEIGHBRIDGE_OPERATOR', 'SITE', 'MIN1', 'S-BJM'],
            ['mnt_mgr', 'Budi Hartono', 'mnt.mgr@miningerp.local', 'MAINTENANCE_MANAGER', 'COMPANY', 'MIN1', null],
            ['doc_ctrl', 'Lilis Suryani', 'doc.ctrl@miningerp.local', 'DOCUMENT_CONTROL', 'ALL_COMPANIES', null, null],
            ['csr', 'Ratna Dewi', 'csr@miningerp.local', 'CSR', 'COMPANY', 'MIN1', null],
            ['auditor', 'Taufik Hidayat', 'auditor@miningerp.local', 'AUDITOR', 'ALL_COMPANIES', null, null],
            ['viewer', 'Gilang Ramadhan', 'viewer@miningerp.local', 'VIEWER', 'ALL_COMPANIES', null, null],
            ['min2_mgr', 'Yusuf Maulana', 'min2.mgr@miningerp.local', 'SITE_MANAGER', 'SITE', 'MIN2', 'S-SMD'],
            ['min2_sales', 'Wati Sulastri', 'min2.sales@miningerp.local', 'SALES', 'SITE', 'MIN2', 'S-SMD'],
        ];

        foreach ($users as [$username, $name, $email, $roleCode, $scope, $companyCode, $siteCode]) {
            $role = Role::where('code', $roleCode)->first();
            if (!$role) {
                continue;
            }
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('Demo!2345'),
                    'status' => 'ACTIVE',
                    'password_changed_at' => now(),
                ]
            );
            $user->roles()->syncWithoutDetaching([
                $role->id => [
                    'scope' => $scope,
                    'company_id' => $companyCode ? \App\Models\Company::where('code', $companyCode)->value('id') : null,
                    'site_id' => $siteCode ? \App\Models\Site::where('code', $siteCode)->value('id') : null,
                ],
            ]);
        }

        $this->command?->info('Demo users: ' . User::count() . ' users');
    }
}
