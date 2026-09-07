<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan penting: Core (roles/permissions/superadmin) → Accounting (COA/mapping)
     * → Alert & Approval workflow → Demo master → Demo users → Demo transaksi.
     * Semua seeder idempotent (guard di masing-masing run()).
     */
    public function run(): void
    {
        $this->call([
            CoreSeeder::class,
            PaperProfileSeeder::class,
            AccountingSeeder::class,
            AlertRuleSeeder::class,
            ApprovalWorkflowSeeder::class,
            DemoSeeder::class,
            UserSeeder::class,
            TransactionSeeder::class,
            NewModulesSeeder::class,
            SeoSeeder::class,
            LetterSeeder::class,
        ]);
    }
}
