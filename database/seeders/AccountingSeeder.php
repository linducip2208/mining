<?php

namespace Database\Seeders;

use App\Models\AccountingMapping;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\Setting;
use App\Models\TaxCode;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public array $coas = [
        // ASSET
        ['1-1000', 'Kas', 'ASSET', 'CASH'],
        ['1-1100', 'Bank', 'ASSET', 'BANK'],
        ['1-1200', 'Piutang Usaha (AR)', 'ASSET', 'AR'],
        ['1-1300', 'Persediaan Finished Goods', 'ASSET', 'INVENTORY'],
        ['1-1310', 'Persediaan Bahan Baku / Material', 'ASSET', 'INVENTORY'],
        ['1-1320', 'Persediaan Sparepart', 'ASSET', 'INVENTORY'],
        ['1-1400', 'Uang Muka PPN Masukan', 'ASSET', 'TAX'],
        ['1-1500', 'Peralatan & Mesin', 'ASSET', 'FIXED_ASSET'],
        ['1-1600', 'Akumulasi Penyusutan', 'ASSET', 'FIXED_ASSET'],
        ['1-1700', 'Deposit Customer', 'ASSET', 'CUSTOMER_DEPOSIT'],
        // LIABILITY
        ['2-1000', 'Hutang Usaha (AP)', 'LIABILITY', 'AP'],
        ['2-1100', 'Hutang Gaji', 'LIABILITY', 'SALARY_PAYABLE'],
        ['2-1200', 'Hutang PPN Keluaran', 'LIABILITY', 'TAX'],
        ['2-1210', 'Hutang PPh 21', 'LIABILITY', 'TAX'],
        ['2-1300', 'Hutang Jangka Panjang', 'LIABILITY', 'LOAN'],
        // EQUITY
        ['3-1000', 'Modal Disetor', 'EQUITY', 'CAPITAL'],
        ['3-2000', 'Laba Ditahan', 'EQUITY', 'RETAINED_EARNINGS'],
        // REVENUE
        ['4-1000', 'Pendapatan Penjualan', 'REVENUE', 'SALES'],
        ['4-2000', 'Pendapatan Lain-lain', 'REVENUE', 'OTHER'],
        ['4-3000', 'Pendapatan Selisih Harga', 'REVENUE', 'VARIANCE'],
        // EXPENSE
        ['5-1000', 'Beban Pokok Penjualan', 'EXPENSE', 'COGS'],
        ['5-2000', 'Beban Gaji', 'EXPENSE', 'SALARY'],
        ['5-2100', 'Beban Insentif Operator', 'EXPENSE', 'INCENTIVE'],
        ['5-3000', 'Beban Pemeliharaan', 'EXPENSE', 'MAINTENANCE'],
        ['5-3100', 'Beban Bahan Bakar', 'EXPENSE', 'FUEL'],
        ['5-4000', 'Beban Administrasi Umum', 'EXPENSE', 'ADMIN'],
        ['5-4100', 'Beban Selisih Harga', 'EXPENSE', 'VARIANCE'],
        ['5-5000', 'Beban Penyusutan', 'EXPENSE', 'DEPRECIATION'],
        ['5-6000', 'Beban CSR', 'EXPENSE', 'CSR'],
        ['5-7000', 'Beban Pajak', 'EXPENSE', 'TAX'],
    ];

    public array $mappings = [
        ['CASH_MAIN', '1-1000', 'Kas utama'],
        ['BANK_MAIN', '1-1100', 'Bank utama'],
        ['AR_TRADE', '1-1200', 'Piutang usaha'],
        ['INVENTORY_FG', '1-1300', 'Persediaan FG'],
        ['INVENTORY_RAW', '1-1310', 'Persediaan bahan baku'],
        ['INVENTORY_SPAREPART', '1-1320', 'Persediaan sparepart'],
        ['INVENTORY_GENERAL', '1-1310', 'Pembelian inventory (default bahan baku)'],
        ['CUSTOMER_DEPOSIT', '1-1700', 'Deposit customer'],
        ['AP_TRADE', '2-1000', 'Hutang usaha'],
        ['SALARY_PAYABLE', '2-1100', 'Hutang gaji'],
        ['TAX_PPN_OUT', '2-1200', 'PPN keluaran'],
        ['TAX_PPN_IN', '1-1400', 'PPN masukan'],
        ['TAX_PPH21_PAYABLE', '2-1210', 'PPh 21'],
        ['SALES_REVENUE', '4-1000', 'Pendapatan penjualan'],
        ['OTHER_REVENUE', '4-2000', 'Pendapatan lain'],
        ['VARIANCE_REVENUE', '4-3000', 'Pendapatan selisih harga'],
        ['VARIANCE_EXPENSE', '5-4100', 'Beban selisih harga'],
        ['COGS', '5-1000', 'HPP'],
        ['SALARY_EXPENSE', '5-2000', 'Beban gaji'],
        ['INCENTIVE_EXPENSE', '5-2100', 'Beban insentif'],
        ['MAINTENANCE_EXPENSE', '5-3000', 'Beban pemeliharaan'],
        ['FUEL_EXPENSE', '5-3100', 'Beban BBM'],
        ['ADMIN_EXPENSE', '5-4000', 'Beban administrasi'],
        ['DEPRECIATION_EXPENSE', '5-5000', 'Beban penyusutan'],
        ['CSR_EXPENSE', '5-6000', 'Beban CSR'],
        ['TAX_EXPENSE', '5-7000', 'Beban pajak'],
        ['FIXED_ASSET', '1-1500', 'Peralatan tetap'],
    ];

    public function run(): void
    {
        foreach ($this->coas as [$code, $name, $type, $subtype]) {
            ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'subtype' => $subtype]);
        }

        foreach ($this->mappings as [$code, $coaCode, $name]) {
            $coa = ChartOfAccount::where('code', $coaCode)->first();
            AccountingMapping::updateOrCreate(['code' => $code], [
                'name' => $name,
                'chart_of_account_id' => $coa->id,
                'module' => 'GENERAL',
            ]);
        }

        // Tax codes (rates configurable — Indonesian rules may change)
        TaxCode::firstOrCreate(['code' => 'PPN11'], ['name' => 'PPN 11%', 'type' => 'PPN_OUT', 'rate' => 11]);
        TaxCode::firstOrCreate(['code' => 'PPN12'], ['name' => 'PPN 12%', 'type' => 'PPN_OUT', 'rate' => 12]);
        TaxCode::firstOrCreate(['code' => 'PPN_IN'], ['name' => 'PPN Masukan 11%', 'type' => 'PPN_IN', 'rate' => 11]);
        TaxCode::firstOrCreate(['code' => 'PBB'], ['name' => 'Pajak Bumi & Bangunan', 'type' => 'PBB', 'rate' => 0.5]);
        TaxCode::firstOrCreate(['code' => 'PPH21'], ['name' => 'PPh 21', 'type' => 'PPH21', 'rate' => 5]);

        // system settings
        Setting::updateOrCreate(['key' => 'inventory.allow_negative_stock'], ['value' => 'false', 'type' => 'bool']);
        Setting::updateOrCreate(['key' => 'inventory.default_warehouse_id'], ['value' => '1', 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'payroll.ptkp_monthly'], ['value' => '4500000', 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'payroll.pph21_rate'], ['value' => '5', 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'tax.default_sales_tax_code'], ['value' => 'PPN11', 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'sales.invoice_require_do'], ['value' => 'true', 'type' => 'bool']);
        Setting::updateOrCreate(['key' => 'weighbridge.allow_weight_override'], ['value' => 'true', 'type' => 'bool']);
        Setting::updateOrCreate(['key' => 'weighbridge.void_require_approval'], ['value' => 'true', 'type' => 'bool']);
        Setting::updateOrCreate(['key' => 'docs.public'], ['value' => 'true', 'type' => 'bool']);
        Setting::updateOrCreate(['key' => 'notification.whatsapp_webhook_url'], ['value' => '', 'type' => 'string']);

        // open fiscal periods for this + next year
        foreach (range(now()->year, now()->year + 1) as $y) {
            foreach (range(1, 12) as $m) {
                FiscalPeriod::firstOrCreate([
                    'company_id' => null,
                    'period' => sprintf('%04d-%02d', $y, $m),
                ], ['status' => 'OPEN']);
            }
        }

        $this->command?->info('Accounting: ' . ChartOfAccount::count() . ' COA, ' . AccountingMapping::count() . ' mappings, ' . TaxCode::count() . ' tax codes');
    }
}
