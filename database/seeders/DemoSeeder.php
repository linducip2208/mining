<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Crusher;
use App\Models\Customer;
use App\Models\DepositService;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PaymentTerm;
use App\Models\Pit;
use App\Models\Shift;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Weighbridge;
use App\Models\Company;
use App\Services\AuditService;
use App\Services\DepositService as DepositServiceAlias;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private const FIRST = ['Budi', 'Siti', 'Agus', 'Dewi', 'Joko', 'Rina', 'Andi', 'Sari', 'Wayan', 'Putu', 'Ahmad', 'Nur', 'Bambang', 'Sri', 'Eko', 'Rudi', 'Tono', 'Wati', 'Dedi', 'Yuli', 'Hendra', 'Maya', 'Fajar', 'Indra', 'Rina', 'Gilang', 'Bagus', 'Ratna', 'Taufik', 'Lilis'];
    private const LAST = ['Santoso', 'Wijaya', 'Pratama', 'Saputra', 'Haryanto', 'Kusuma', 'Nugroho', 'Setiawan', 'Halim', 'Maulana', 'Firmansyah', 'Ramadhan', 'Gunawan', 'Kurniawan', 'Susanto'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Production: demo master diblokir.');
            return;
        }
        // idempotency guard: skip if demo master already seeded
        if (Site::where('code', 'S-BJM')->exists() && Warehouse::where('code', 'WST1')->exists()) {
            $this->command?->info('Demo data sudah ada — seeding master dilewati.');
            return;
        }

        // disable audit during seeding for speed
        AuditService::disable();

        $this->command?->info('Seeding demo data...');

        // ===== COMPANIES =====
        $c1 = Company::firstOrCreate(['code' => 'MIN1'], ['name' => 'PT Borneo Mining Utama', 'npwp' => '01.234.567.8-901.000', 'city' => 'Balikpapan', 'status' => true]);
        $c2 = Company::firstOrCreate(['code' => 'MIN2'], ['name' => 'PT Sumber Daya Energi', 'npwp' => '02.345.678.9-012.000', 'city' => 'Samarinda', 'status' => true]);

        $sites = [];
        $siteDefs = [
            ['S-BJM', 'Site Banjarmasin', 'MINE', $c1],
            ['S-KTN', 'Site Kutai Timur', 'MINE', $c1],
            ['S-PLT', 'Site Pelabuhan Kelanis', 'PORT', $c2],
            ['S-SMD', 'Site Samarinda Utara', 'MINE', $c2],
            ['S-CRS', 'Plant Crushing Balikpapan', 'PLANT', $c1],
        ];
        foreach ($siteDefs as [$code, $name, $type, $company]) {
            $sites[] = Site::firstOrCreate(['company_id' => $company->id, 'code' => $code], ['name' => $name, 'type' => $type, 'status' => true]);
        }
        [$s1, $s2, $s3, $s4, $s5] = $sites;

        // ===== SHIFTS =====
        $shiftPagi = Shift::create(['company_id' => $c1->id, 'code' => 'PAGI', 'name' => 'Shift Pagi', 'start_time' => '07:00', 'end_time' => '15:00', 'status' => true]);
        $shiftSiang = Shift::create(['company_id' => $c1->id, 'code' => 'SIANG', 'name' => 'Shift Siang', 'start_time' => '15:00', 'end_time' => '23:00', 'status' => true]);
        Shift::create(['company_id' => $c1->id, 'code' => 'MALAM', 'name' => 'Shift Malam', 'start_time' => '23:00', 'end_time' => '07:00', 'status' => true]);

        // ===== WAREHOUSES / STOCKPILES =====
        $warehouses = [];
        foreach ($sites as $i => $site) {
            $warehouses[] = Warehouse::create(['company_id' => $site->company_id, 'site_id' => $site->id, 'code' => 'WST' . ($i + 1), 'name' => 'Stockpile ' . $site->name, 'type' => $site->type === 'MINE' ? 'STOCKPILE' : 'WAREHOUSE', 'status' => true]);
        }
        $whSpare = Warehouse::create(['company_id' => $c1->id, 'site_id' => $s5->id, 'code' => 'WSPARE', 'name' => 'Gudang Sparepart Pusat', 'type' => 'WAREHOUSE', 'status' => true]);

        // ===== PITS =====
        $pits = [];
        foreach ([$s1, $s2, $s4] as $site) {
            foreach (['A', 'B'] as $p) {
                $pits[] = Pit::create(['site_id' => $site->id, 'code' => 'PIT-' . $p, 'name' => 'Pit ' . $p . ' ' . $site->name, 'area_hectare' => rand(20, 80), 'status' => true]);
            }
        }

        // ===== CRUSHERS =====
        $crushers = [
            Crusher::create(['site_id' => $s5->id, 'code' => 'CR-01', 'name' => 'Crusher Primer', 'capacity_ton_hour' => 500, 'warehouse_id' => $warehouses[4]->id, 'status' => true]),
            Crusher::create(['site_id' => $s5->id, 'code' => 'CR-02', 'name' => 'Crusher Sekunder', 'capacity_ton_hour' => 300, 'warehouse_id' => $warehouses[4]->id, 'status' => true]),
        ];

        // ===== WEIGHBRIDGES =====
        $wbs = [];
        foreach ([$s1, $s3, $s5] as $site) {
            $wb = Weighbridge::create(['site_id' => $site->id, 'code' => 'WB-' . $site->code, 'name' => 'Timbangan ' . $site->name, 'max_capacity' => 80000, 'status' => true]);
            $wb->calibrations()->create(['calibration_date' => now()->subMonths(2), 'version' => 'CAL-2026-' . $wb->id, 'certificate_no' => 'SERT-' . rand(10000, 99999), 'valid_until' => now()->addMonths(10)]);
            $wbs[] = $wb;
        }

        // ===== UNITS, CATEGORIES, ITEMS =====
        $unitTon = Unit::firstOrCreate(['code' => 'TON'], ['name' => 'Ton']);
        $unitPcs = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pcs']);
        $unitLt = Unit::firstOrCreate(['code' => 'LTR'], ['name' => 'Liter']);
        $unitM3 = Unit::firstOrCreate(['code' => 'M3'], ['name' => 'Meter Kubik']);

        $catRaw = ItemCategory::firstOrCreate(['company_id' => $c1->id, 'code' => 'RAW'], ['name' => 'Bahan Galian', 'type' => 'RAW', 'status' => true]);
        $catProd = ItemCategory::firstOrCreate(['company_id' => $c1->id, 'code' => 'PROD'], ['name' => 'Produk Batu', 'type' => 'PRODUCT', 'status' => true]);
        $catSpare = ItemCategory::firstOrCreate(['company_id' => $c1->id, 'code' => 'SPARE'], ['name' => 'Sparepart Alat Berat', 'type' => 'SPAREPART', 'status' => true]);
        $catFuel = ItemCategory::firstOrCreate(['company_id' => $c1->id, 'code' => 'FUEL'], ['name' => 'BBM', 'type' => 'FUEL', 'status' => true]);
        $catCons = ItemCategory::firstOrCreate(['company_id' => $c1->id, 'code' => 'CONS'], ['name' => 'Consumable', 'type' => 'CONSUMABLE', 'status' => true]);

        $items = [];
        // 20 products/materials
        $materialDefs = [
            ['BAT-BARU', 'Batu Bara GAR 4200', 'RAW', $catRaw, $unitTon, 450000],
            ['BAT-BAR2', 'Batu Bara GAR 5000', 'RAW', $catRaw, $unitTon, 650000],
            ['NKL-A', 'Nikel Laterit 1.8%', 'RAW', $catRaw, $unitTon, 350000],
            ['NKL-B', 'Nikel Laterit 2.0%', 'RAW', $catRaw, $unitTon, 420000],
            ['BST-1-2', 'Batu Split 1-2 cm', 'PRODUCT', $catProd, $unitTon, 280000],
            ['BST-2-3', 'Batu Split 2-3 cm', 'PRODUCT', $catProd, $unitTon, 265000],
            ['BST-3-5', 'Batu Split 3-5 cm', 'PRODUCT', $catProd, $unitTon, 250000],
            ['ABU-BATU', 'Abu Batu (Screening)', 'PRODUCT', $catProd, $unitTon, 180000],
            ['PASIR-BRS', 'Pasir Bersih', 'PRODUCT', $catProd, $unitM3, 150000],
            ['SOLI', 'Soli/Material Urug', 'PRODUCT', $catProd, $unitTon, 90000],
            ['BCG-0-5', 'Batu Kricak 0-5 mm', 'PRODUCT', $catProd, $unitTon, 240000],
            ['BASE-CRS', 'Base Course Crusher', 'PRODUCT', $catProd, $unitTon, 210000],
        ];
        foreach ($materialDefs as [$code, $name, $type, $cat, $unit, $price]) {
            $items[] = Item::create(['code' => $code, 'name' => $name, 'item_category_id' => $cat->id, 'type' => $type, 'unit_id' => $unit->id, 'standard_cost' => round($price * 0.7), 'min_stock' => $type === 'PRODUCT' ? 500 : 0, 'reorder_point' => $type === 'PRODUCT' ? 1000 : 0, 'status' => true]);
        }
        // spareparts + fuel
        $spareDefs = [
            ['FLT-OIL', 'Filter Oli Komatsu PC200', $catSpare, $unitPcs, 850000, 10],
            ['FLT-AIR', 'Filter Udara CAT 320', $catSpare, $unitPcs, 1200000, 8],
            ['BRG-22220', 'Bearing 22220 Spherical', $catSpare, $unitPcs, 3500000, 4],
            ['BRG-6316', 'Bearing 6316 Deep Groove', $catSpare, $unitPcs, 2200000, 6],
            ['VBLT-C320', 'V-Belt C320 Crusher', $catSpare, $unitPcs, 480000, 12],
            ['RUB-CONV', 'Karet Conveyor Belt 1m', $catSpare, $unitPcs, 2400000, 5],
            ['ENG-OIL', 'Oli Mesin SAE 15W-40 (20L)', $catCons, $unitPcs, 1450000, 20],
            ['GRS-EP2', 'Grease EP2 (18kg)', $catCons, $unitPcs, 1250000, 15],
            ['HFO-SOLAR', 'Solar HSD', $catFuel, $unitLt, 14500, 20000],
        ];
        foreach ($spareDefs as [$code, $name, $cat, $unit, $cost, $min]) {
            $items[] = Item::create(['code' => $code, 'name' => $name, 'item_category_id' => $cat->id, 'type' => $cat->type, 'unit_id' => $unit->id, 'standard_cost' => $cost, 'min_stock' => $min, 'reorder_point' => $min * 1.5, 'status' => true]);
        }

        // ===== EMPLOYEES (100+) =====
        $employees = [];
        $positions = ['Operator Excavator', 'Operator Dump Truck', 'Operator Loader', 'Operator Crusher', 'Mekanik', 'Elektrikian', 'Supervisor Tambang', 'Weighbridge Operator', 'Admin Site', 'Security', 'Helper', 'Foreman'];
        for ($i = 1; $i <= 100; $i++) {
            $company = $i <= 60 ? $c1 : $c2;
            $site = $sites[array_rand($sites)];
            $employees[] = Employee::create([
                'code' => sprintf('EMP-%04d', $i),
                'name' => self::FIRST[$i % count(self::FIRST)] . ' ' . self::LAST[$i % count(self::LAST)],
                'company_id' => $company->id,
                'site_id' => $site->id,
                'position' => $positions[$i % count($positions)],
                'employment_type' => $i % 5 === 0 ? 'CONTRACT' : 'PERMANENT',
                'basic_salary' => rand(45, 120) * 100000,
                'shift_id' => $i % 3 === 0 ? $shiftSiang->id : $shiftPagi->id,
                'join_date' => now()->subYears(rand(1, 8))->subDays(rand(0, 300)),
                'phone' => '0812' . sprintf('%08d', $i * 7654321 % 100000000),
                'bank_name' => 'Bank Mandiri',
                'bank_account' => '1234' . sprintf('%06d', $i * 31),
                'status' => 'ACTIVE',
            ]);
        }

        // operators & drivers & technicians
        $employees = collect($employees);
        $operators = $employees->filter(fn ($e) => str_contains($e->position, 'Operator'))->values();
        $drivers = $employees->filter(fn ($e) => str_contains($e->position, 'Dump Truck'))->values();
        $mechanics = $employees->filter(fn ($e) => str_contains($e->position, 'Mekanik'))->values();
        $wbOperators = $employees->filter(fn ($e) => str_contains($e->position, 'Weighbridge'))->values();

        // ===== EQUIPMENT (30) & VEHICLES (50) =====
        $equipmentList = [];
        $eqDefs = [
            ['EXC', 'EXCAVATOR', 'Komatsu PC2000', 200],
            ['EXC', 'EXCAVATOR', 'CAT 336', 200],
            ['LDR', 'LOADER', 'CAT 966H', 200],
            ['DZB', 'DOZER', 'Komatsu D155', 200],
            ['GRD', 'GRADER', 'CAT 14M', 200],
            ['DRL', 'DRILL', 'Sandvik D45KS', 200],
        ];
        $idx = 1;
        foreach ($eqDefs as [$prefix, $type, $model, $cap]) {
            for ($k = 1; $k <= 5; $k++) {
                $eq = Equipment::create([
                    'company_id' => $c1->id,
                    'site_id' => $sites[$idx % 5]->id,
                    'code' => $prefix . sprintf('%02d', $idx),
                    'name' => $model . ' #' . $idx,
                    'type' => $type,
                    'brand' => explode(' ', $model)[0],
                    'model' => $model,
                    'ownership' => $idx % 4 === 0 ? 'RENTED' : 'OWNED',
                    'meter_reading' => rand(1000, 20000),
                    'status' => 'AVAILABLE',
                ]);
                $equipmentList[] = $eq;
                $idx++;
            }
        }

        // vehicles
        $vehicles = [];
        for ($v = 1; $v <= 50; $v++) {
            $vehicles[] = Equipment::create([
                'company_id' => $v <= 30 ? $c1->id : $c2->id,
                'site_id' => $sites[$v % 5]->id,
                'code' => 'DT' . sprintf('%02d', $v),
                'name' => 'Dump Truck HD785 #' . $v,
                'type' => 'DUMP_TRUCK',
                'brand' => 'Komatsu',
                'model' => 'HD785',
                'plate_no' => 'B ' . (9000 + $v) . ' MT',
                'ownership' => 'OWNED',
                'meter_reading' => rand(5000, 50000),
                'status' => 'AVAILABLE',
            ]);
        }

        // ===== CUSTOMERS (100) & SUPPLIERS (100) =====
        $paymentTerms = [
            PaymentTerm::firstOrCreate(['code' => 'CASH'], ['name' => 'Tunai', 'days' => 0]),
            PaymentTerm::firstOrCreate(['code' => 'NET30'], ['name' => 'Net 30 Hari', 'days' => 30]),
            PaymentTerm::firstOrCreate(['code' => 'NET60'], ['name' => 'Net 60 Hari', 'days' => 60]),
        ];

        $custNames = ['PT Konstruksi', 'CV Bangun', 'PT Beton', 'PT Jalan Tol', 'CV Tambang Jaya', 'PT Energi', 'PT Baja', 'CV Mitra', 'PT Batubara', 'PT Semen'];
        $cities = ['Balikpapan', 'Samarinda', 'Banjarmasin', 'Tarakan', 'Bontang', 'Loa Janan', 'Sengata', 'Tenggarong'];
        for ($i = 1; $i <= 100; $i++) {
            Customer::create([
                'company_id' => $i <= 60 ? $c1->id : $c2->id,
                'code' => sprintf('CUS-%04d', $i),
                'name' => $custNames[$i % count($custNames)] . ' ' . chr(65 + $i % 26) . sprintf('%02d', $i % 100) . ' ' . $cities[$i % count($cities)],
                'city' => $cities[$i % count($cities)],
                'phone' => '0811' . sprintf('%08d', $i * 55554321 % 100000000),
                'payment_term_id' => $paymentTerms[$i % 3]->id,
                'credit_limit' => rand(100, 1000) * 1000000,
                'group' => $i % 3 === 0 ? 'KONTRAKTOR' : ($i % 3 === 1 ? 'PROYEK' : 'TOKO'),
                'status' => true,
            ]);
        }

        $supNames = ['PT Sumber Alat', 'CV Teknik', 'PT Oli Nusantara', 'PT Suku Cadang', 'CV Logistik', 'PT BBM Pusat', 'PT Ban Ban', 'CV Jasa Servis'];
        for ($i = 1; $i <= 100; $i++) {
            Supplier::create([
                'company_id' => $i <= 60 ? $c1->id : $c2->id,
                'code' => sprintf('SUP-%04d', $i),
                'name' => $supNames[$i % count($supNames)] . ' ' . chr(65 + $i % 26) . sprintf('%02d', $i % 100),
                'city' => $cities[$i % count($cities)],
                'payment_term_id' => $paymentTerms[$i % 3]->id,
                'type' => ['VENDOR', 'SERVICE', 'TRANSPORT'][$i % 3],
                'status' => true,
            ]);
        }

        // ===== ATTENDANCE (last 14 days) =====
        foreach ($employees as $i => $emp) {
            for ($d = 0; $d < 14; $d++) {
                $date = today()->subDays($d);
                if ($date->isSunday()) {
                    continue;
                }
                $status = 'PRESENT';
                if (rand(1, 30) === 1) $status = 'LATE';
                if (rand(1, 40) === 1) $status = 'LEAVE';
                if (rand(1, 50) === 1) $status = 'ABSENT';
                $lateMin = $status === 'LATE' ? rand(16, 90) : 0;
                $otMin = rand(1, 6) === 1 ? rand(60, 180) : 0;

                Attendance::create([
                    'employee_id' => $emp->id,
                    'date' => $date,
                    'check_in' => '07:' . sprintf('%02d', rand(0, $status === 'LATE' ? 59 : 14)),
                    'check_out' => $otMin > 0 ? '17:' . sprintf('%02d', rand(0, 59)) : '16:00',
                    'status' => $status,
                    'late_minutes' => $lateMin,
                    'overtime_minutes' => $otMin,
                    'source' => 'FINGERPRINT',
                ]);
            }
        }

        AuditService::enable();

        $this->command?->info('Demo: ' . Company::count() . ' companies, ' . Site::count() . ' sites, ' . Employee::count() . ' employees, ' . Equipment::count() . ' equipment, ' . Customer::count() . ' customers, ' . Supplier::count() . ' suppliers, ' . Item::count() . ' items, ' . Attendance::count() . ' attendance records');
    }
}
