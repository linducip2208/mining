<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoreSeeder extends Seeder
{
    public const MODULES = [
        'dashboard' => 'Dashboard',
        'user' => 'Manajemen Pengguna',
        'role' => 'Manajemen Peran',
        'company' => 'Perusahaan',
        'branch' => 'Cabang',
        'site' => 'Site Tambang',
        'division' => 'Divisi',
        'department' => 'Departemen',
        'cost_center' => 'Pusat Biaya',
        'employee' => 'Karyawan',
        'attendance' => 'Absensi',
        'leave' => 'Cuti',
        'overtime' => 'Lembur',
        'payroll' => 'Payroll',
        'incentive' => 'Insentif Operator',
        'mining' => 'Operasi Tambang',
        'weighbridge' => 'Timbangan',
        'production' => 'Produksi Crusher',
        'inventory' => 'Inventory',
        'stock' => 'Stok',
        'procurement' => 'Pengadaan',
        'purchase_request' => 'Permintaan Pembelian',
        'purchase_order' => 'Order Pembelian',
        'goods_receipt' => 'Penerimaan Barang',
        'vendor_bill' => 'Tagihan Vendor',
        'sales' => 'Penjualan',
        'sales_order' => 'Order Penjualan',
        'delivery_order' => 'Surat Jalan',
        'invoice' => 'Faktur Penjualan',
        'payment' => 'Pembayaran',
        'deposit' => 'Deposit Customer',
        'price' => 'Harga',
        'price_variance' => 'Selisih Harga',
        'asset' => 'Aset',
        'maintenance' => 'Pemeliharaan',
        'work_order' => 'Work Order',
        'finance' => 'Keuangan',
        'journal' => 'Jurnal',
        'ledger' => 'Buku Besar',
        'report' => 'Laporan',
        'tax' => 'Pajak',
        'document' => 'Dokumen',
        'csr' => 'CSR',
        'approval' => 'Persetujuan',
        'notification' => 'Notifikasi',
        'audit' => 'Audit Trail',
        'setting' => 'Pengaturan',
        'fleet' => 'Armada & Alat Berat',
        'fuel' => 'BBM',
        'tire' => 'Ban',
        'dispatch' => 'Dispatch & Hauling',
        'stockpile' => 'Stockpile',
        'quality' => 'Quality Control',
        'cost' => 'Biaya Tambang',
        'contract' => 'Kontrak',
        'budget' => 'Budget',
        'hse' => 'HSE / K3',
        'compliance' => 'Compliance',
        'fiscal' => 'Periode Fiskal',
        'telematics' => 'Telematics',
        'ai' => 'AI Copilot',
        'forecast' => 'Forecast & Anomali',
        'executive' => 'Executive Dashboard',
    ];

    public const ACTIONS = [
        'view' => 'Lihat',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
        'approve' => 'Setujui',
        'reject' => 'Tolak',
        'cancel' => 'Batalkan',
        'post' => 'Posting',
        'unpost' => 'Batal Posting',
        'print' => 'Cetak',
        'export' => 'Ekspor',
        'import' => 'Impor',
        'void' => 'Void',
        'reopen' => 'Buka Ulang',
    ];

    public const ROLES = [
        'SUPER_ADMIN' => 'Super Admin',
        'SYSTEM_ADMIN' => 'System Admin',
        'DIRECTOR' => 'Direktur',
        'GENERAL_MANAGER' => 'General Manager',
        'MINE_MANAGER' => 'Mine Manager',
        'SITE_MANAGER' => 'Site Manager',
        'PRODUCTION_MANAGER' => 'Production Manager',
        'FINANCE_MANAGER' => 'Finance Manager',
        'ACCOUNTING' => 'Accounting',
        'HR_MANAGER' => 'HR Manager',
        'HR_STAFF' => 'HR Staff',
        'PAYROLL_STAFF' => 'Payroll Staff',
        'PURCHASING' => 'Purchasing',
        'WAREHOUSE_MANAGER' => 'Warehouse Manager',
        'WAREHOUSE_STAFF' => 'Warehouse Staff',
        'SALES_MANAGER' => 'Sales Manager',
        'SALES' => 'Sales',
        'WEIGHBRIDGE_OPERATOR' => 'Operator Timbangan',
        'MINE_OPERATOR' => 'Operator Tambang',
        'MAINTENANCE_MANAGER' => 'Maintenance Manager',
        'MAINTENANCE_TECHNICIAN' => 'Teknisi',
        'DOCUMENT_CONTROL' => 'Document Control',
        'CSR' => 'CSR',
        'AUDITOR' => 'Auditor',
        'VIEWER' => 'Viewer',
        'FLEET_MANAGER' => 'Fleet Manager',
        'DISPATCHER' => 'Dispatcher',
        'FUEL_ADMIN' => 'Fuel Admin',
        'FUEL_OPERATOR' => 'Fuel Operator',
        'TIRE_OFFICER' => 'Tire Officer',
        'QUALITY_OFFICER' => 'Quality Officer',
        'LAB_OFFICER' => 'Lab Officer',
        'HSE_MANAGER' => 'HSE Manager',
        'HSE_OFFICER' => 'HSE Officer',
        'BUDGET_CONTROLLER' => 'Budget Controller',
        'CONTRACT_MANAGER' => 'Contract Manager',
        'COMPLIANCE_OFFICER' => 'Compliance Officer',
    ];

    public const EXTRA_PERMISSIONS = [
        ['user.activate', 'Pengguna - Aktivasi', 'user', 'activate'],
        ['user.deactivate', 'Pengguna - Nonaktif', 'user', 'activate'],
        ['user.suspend', 'Pengguna - Suspend', 'user', 'activate'],
        ['user.unlock', 'Pengguna - Buka Kunci', 'user', 'activate'],
        ['user.reset_password', 'Pengguna - Reset Password', 'user', 'update'],
        ['user.assign_role', 'Pengguna - Assign Role', 'user', 'update'],
        ['user.view_login_history', 'Pengguna - Lihat Login History', 'user', 'view'],
        ['user.logout_session', 'Pengguna - Akhiri Sesi', 'user', 'update'],
        ['fuel.issue', 'BBM - Issue', 'fuel', 'update'],
        ['fuel.approve', 'BBM - Approve', 'fuel', 'approve'],
        ['fuel.adjust', 'BBM - Adjustment', 'fuel', 'update'],
        ['dispatch.assign', 'Dispatch - Assign', 'dispatch', 'create'],
        ['dispatch.close', 'Dispatch - Close Trip', 'dispatch', 'update'],
        ['stockpile.adjust', 'Stockpile - Adjustment', 'stockpile', 'update'],
        ['stockpile.reconcile', 'Stockpile - Rekonsiliasi', 'stockpile', 'approve'],
        ['stockpile.approve', 'Stockpile - Approve', 'stockpile', 'approve'],
        ['quality.test', 'Quality - Uji Lab', 'quality', 'create'],
        ['quality.approve', 'Quality - Approve', 'quality', 'approve'],
        ['quality.release', 'Quality - Release Hold', 'quality', 'approve'],
        ['budget.approve', 'Budget - Approve', 'budget', 'approve'],
        ['budget.revise', 'Budget - Revisi', 'budget', 'update'],
        ['budget.override', 'Budget - Override Over-Budget', 'budget', 'approve'],
        ['hse.close', 'HSE - Close Case', 'hse', 'approve'],
        ['hse.approve', 'HSE - Approve', 'hse', 'approve'],
        ['contract.approve', 'Kontrak - Approve', 'contract', 'approve'],
        ['contract.override', 'Kontrak - Override Over-Contract', 'contract', 'approve'],
        ['compliance.update', 'Compliance - Update', 'compliance', 'update'],
        ['fiscal.close', 'Periode - Close', 'fiscal', 'update'],
        ['fiscal.reopen', 'Periode - Reopen', 'fiscal', 'update'],
    ];

    public function run(): void
    {
        // permissions
        foreach (self::MODULES as $module => $label) {
            foreach (self::ACTIONS as $action => $actionLabel) {
                Permission::updateOrCreate(
                    ['code' => "{$module}.{$action}"],
                    ['name' => "{$label} - {$actionLabel}", 'module' => $module, 'group' => $action]
                );
            }
        }

        // roles
        foreach (self::ROLES as $code => $name) {
            Role::updateOrCreate(['code' => $code], ['name' => $name, 'is_system' => true]);
        }

        // permission granular tambahan (di luar matriks modul×aksi standar)
        foreach (self::EXTRA_PERMISSIONS as [$code, $name, $module, $group]) {
            Permission::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'module' => $module, 'group' => $group]
            );
        }

        $all = Permission::pluck('id');
        $viewOnly = Permission::where('group', 'view')->orWhere('group', 'export')->orWhere('group', 'print')->pluck('id');

        // role => permission assignment
        Role::where('code', 'SUPER_ADMIN')->first()->permissions()->sync($all);

        Role::where('code', 'AUDITOR')->first()->permissions()->sync($viewOnly);
        Role::where('code', 'VIEWER')->first()->permissions()->sync($viewOnly);

        Role::where('code', 'DIRECTOR')->first()->permissions()->sync(
            Permission::whereNotIn('module', ['setting', 'user', 'role'])->pluck('id')
        );

        Role::where('code', 'ACCOUNTING')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'finance', 'journal', 'ledger', 'tax', 'fiscal', 'cost', 'report', 'vendor_bill', 'payment', 'invoice', 'deposit', 'audit'])->get()
        );

        Role::where('code', 'FINANCE_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'finance', 'journal', 'ledger', 'tax', 'fiscal', 'budget', 'contract', 'cost', 'report', 'vendor_bill', 'payment', 'invoice', 'deposit', 'price', 'price_variance', 'audit', 'approval'])->get()
        );

        Role::where('code', 'SALES_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'sales', 'sales_order', 'delivery_order', 'invoice', 'price', 'price_variance', 'deposit', 'contract', 'customer_report', 'report'])->get()
        );

        Role::where('code', 'SALES')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'sales', 'sales_order', 'delivery_order'])->where(function ($q) {
                $q->whereNotIn('group', ['post', 'unpost', 'void']);
            })->get()
        );

        Role::where('code', 'PURCHASING')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'procurement', 'purchase_request', 'purchase_order', 'goods_receipt', 'vendor_bill'])->whereNotIn('group', ['unpost', 'void'])->get()
        );

        Role::where('code', 'WAREHOUSE_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'inventory', 'stock', 'goods_receipt', 'report', 'approval'])->get()
        );

        Role::where('code', 'WEIGHBRIDGE_OPERATOR')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'weighbridge'])->get()
        );

        Role::where('code', 'HR_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'employee', 'attendance', 'leave', 'overtime', 'payroll', 'incentive', 'report'])->get()
        );

        Role::where('code', 'PAYROLL_STAFF')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'payroll', 'incentive', 'attendance', 'overtime'])->get()
        );

        Role::where('code', 'MINE_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'mining', 'production', 'dispatch', 'stockpile', 'quality', 'cost', 'incentive', 'report'])->get()
        );

        Role::where('code', 'MAINTENANCE_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'maintenance', 'work_order', 'asset', 'inventory', 'report'])->get()
        );

        Role::where('code', 'FLEET_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'fleet', 'asset', 'fuel', 'tire', 'dispatch', 'maintenance', 'work_order', 'report', 'approval'])->get()
        );

        Role::where('code', 'DISPATCHER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'dispatch', 'mining', 'weighbridge', 'report'])->whereNotIn('group', ['delete', 'void'])->get()
        );

        Role::where('code', 'FUEL_ADMIN')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'fuel', 'report', 'approval'])->get()
        );

        Role::where('code', 'FUEL_OPERATOR')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'fuel'])->whereIn('group', ['view', 'create', 'update'])->get()
        );

        Role::where('code', 'TIRE_OFFICER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'tire', 'fleet', 'report'])->whereNotIn('group', ['delete'])->get()
        );

        Role::where('code', 'QUALITY_OFFICER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'quality', 'production', 'report', 'approval'])->get()
        );

        Role::where('code', 'LAB_OFFICER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'quality'])->whereIn('group', ['view', 'create', 'update'])->get()
        );

        Role::where('code', 'HSE_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'hse', 'compliance', 'report', 'approval'])->get()
        );

        Role::where('code', 'HSE_OFFICER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'hse'])->whereNotIn('group', ['delete'])->get()
        );

        Role::where('code', 'BUDGET_CONTROLLER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'budget', 'finance', 'ledger', 'report', 'approval'])->get()
        );

        Role::where('code', 'CONTRACT_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'contract', 'sales', 'procurement', 'report', 'approval'])->get()
        );

        Role::where('code', 'COMPLIANCE_OFFICER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'compliance', 'document', 'report'])->get()
        );

        Role::where('code', 'SYSTEM_ADMIN')->first()->permissions()->sync($all);

        Role::where('code', 'GENERAL_MANAGER')->first()->permissions()->sync(
            Permission::whereNotIn('module', ['setting', 'user', 'role'])->pluck('id')
        );

        Role::where('code', 'SITE_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'mining', 'production', 'dispatch', 'weighbridge', 'inventory', 'stockpile', 'quality', 'fuel', 'hse', 'cost', 'maintenance', 'work_order', 'incentive', 'approval', 'report', 'audit'])->get()
        );

        Role::where('code', 'PRODUCTION_MANAGER')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'production', 'mining', 'inventory', 'stock', 'stockpile', 'quality', 'cost', 'incentive', 'approval', 'report'])->get()
        );

        Role::where('code', 'HR_STAFF')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'employee', 'attendance', 'leave', 'overtime', 'report'])->whereNotIn('group', ['approve', 'post', 'delete'])->get()
        );

        Role::where('code', 'WAREHOUSE_STAFF')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'inventory', 'stock', 'goods_receipt', 'report'])->whereNotIn('group', ['approve', 'post', 'delete'])->get()
        );

        Role::where('code', 'MINE_OPERATOR')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'mining'])->whereNotIn('group', ['approve', 'post', 'delete', 'void'])->get()
        );

        Role::where('code', 'MAINTENANCE_TECHNICIAN')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'maintenance', 'work_order', 'asset'])->whereNotIn('group', ['approve', 'post', 'delete'])->get()
        );

        Role::where('code', 'DOCUMENT_CONTROL')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'document', 'approval', 'report'])->get()
        );

        Role::where('code', 'CSR')->first()->permissions()->sync(
            Permission::whereIn('module', ['dashboard', 'csr', 'approval', 'report'])->get()
        );

        // super admin user — DEMO CREDENTIAL, never seed in production.
        // Structural seeds above (roles/permissions) are production-safe.
        if (!app()->environment('production')) {
            $admin = User::updateOrCreate(
                ['username' => 'superadmin'],
                [
                    'name' => 'Super Admin',
                    'email' => 'admin@miningerp.local',
                    'password' => Hash::make('Admin!2345'),
                    'status' => 'ACTIVE',
                    'password_changed_at' => now(),
                ]
            );
            $admin->roles()->sync([Role::where('code', 'SUPER_ADMIN')->first()->id]);
        } else {
            $this->command?->warn('Production: demo superadmin dilewati. Buat admin via tinker dengan password kuat.');
        }

        $this->command?->info('Core seeded: ' . Permission::count() . ' permissions, ' . Role::count() . ' roles, superadmin/admin@miningerp.local');
    }
}
