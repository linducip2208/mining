<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\FuelTank;
use App\Models\Item;
use App\Models\Pit;
use App\Models\QualityParameter;
use App\Models\Shift;
use App\Models\Site;
use App\Models\Stockpile;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\ComplianceService;
use App\Services\CostEngine;
use App\Services\DispatchService;
use App\Services\FleetService;
use App\Services\FuelService;
use App\Services\HseService;
use App\Services\NumberingService;
use App\Services\QualityService;
use App\Services\StockpileService;
use App\Services\TireService;
use Illuminate\Database\Seeder;

/**
 * Demo data modul baru: fleet, fuel, tire, dispatch, stockpile,
 * quality, contract, budget, HSE, compliance.
 * Idempotent (guard) + non-production guard seperti DemoSeeder.
 */
class NewModulesSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Production: demo modul baru diblokir.');
            return;
        }
        if (FuelTank::exists()) {
            $this->command?->info('Demo modul baru sudah ada — dilewati.');
            return;
        }

        srand(42);
        AuditService::disable();

        $c1 = Company::where('code', 'MIN1')->first() ?? Company::first();
        $sites = Site::orderBy('id')->get();
        $shifts = Shift::orderBy('id')->get();
        $operators = Employee::where('status', 'ACTIVE')->orderBy('id')->get();
        if (!$c1 || $sites->isEmpty()) {
            $this->command?->warn('Master dasar belum ada — jalankan DemoSeeder dulu.');
            AuditService::enable();
            return;
        }

        $this->seedFleet($c1, $sites, $shifts, $operators);
        $this->seedFuel($c1, $sites);
        $this->seedTires($c1);
        $this->seedDispatch($c1, $sites, $shifts);
        $this->seedStockpile($c1, $sites);
        $this->seedQuality();
        $this->seedContracts($c1, $sites);
        $this->seedBudget($c1, $sites);
        $this->seedHse($c1, $sites, $operators);
        $this->seedCompliance($c1, $sites, $operators);

        AuditService::enable();
        $this->command?->info('Demo modul baru selesai.');
    }

    protected function seedFleet($c1, $sites, $shifts, $operators): void
    {
        $cats = [
            ['EXC', 'Excavator', 'HEAVY', 28, 15],
            ['LDR', 'Wheel Loader', 'HEAVY', 22, 15],
            ['DTT', 'Dump Truck', 'HEAVY', 12, 20],
            ['BLD', 'Bulldozer', 'HEAVY', 30, 15],
            ['GRD', 'Grader', 'HEAVY', 18, 15],
            ['LV', 'Light Vehicle', 'VEHICLE', 4, 25],
        ];
        $catIds = [];
        foreach ($cats as [$code, $name, $type, $lph, $warn]) {
            $cat = EquipmentCategory::firstOrCreate(['code' => $code], [
                'name' => $name, 'type' => $type, 'standard_fuel_lph' => $lph,
                'fuel_warning_pct' => $warn, 'status' => true, 'created_by' => 1,
            ]);
            $catIds[$type === 'VEHICLE' ? 'LV' : $code] = $cat->id;
        }
        // tautkan kategori ke equipment existing per tipe
        $typeMap = ['EXCAVATOR' => 'EXC', 'LOADER' => 'LDR', 'DUMP_TRUCK' => 'DTT', 'DOZER' => 'BLD', 'GRADER' => 'GRD', 'DRILL' => 'EXC'];
        foreach (Equipment::all() as $eq) {
            $cc = $typeMap[$eq->type] ?? null;
            if ($cc && isset($catIds[$cc])) {
                $eq->update(['equipment_category_id' => $catIds[$cc]]);
            }
        }
        // top-up equipment hingga ~100
        $have = Equipment::count();
        for ($i = $have + 1; $i <= 100; $i++) {
            Equipment::create([
                'company_id' => $c1->id, 'site_id' => $sites[$i % $sites->count()]->id,
                'code' => 'SUP' . sprintf('%02d', $i), 'name' => 'Support Unit #' . $i,
                'type' => 'OTHER', 'brand' => 'Toyota', 'model' => 'Hilux',
                'ownership' => 'OWNED', 'meter_reading' => rand(1000, 9000), 'status' => 'AVAILABLE',
                'equipment_category_id' => $catIds['LV'], 'created_by' => 1,
            ]);
        }
        // 150 kendaraan ringan
        $vtypes = ['LV', 'BUS', 'AMBULANCE', 'FUEL_TRUCK', 'WATER_TRUCK'];
        for ($v = 1; $v <= 150; $v++) {
            Vehicle::create([
                'company_id' => $c1->id, 'code' => 'VH' . sprintf('%03d', $v),
                'plate_no' => 'B ' . (1000 + $v) . ' MN', 'name' => 'Kendaraan Operasional #' . $v,
                'type' => $vtypes[$v % 5], 'capacity_ton' => $v % 5 === 1 ? 20 : 2,
                'status' => 'AVAILABLE',
            ]);
        }
        // 500 meter logs + 200 inspeksi
        $units = Equipment::orderBy('id')->get();
        $hm = [];
        foreach ($units as $u) {
            $hm[$u->id] = (float) ($u->meter_reading ?? 1000);
        }
        for ($i = 0; $i < 500; $i++) {
            $u = $units[$i % $units->count()];
            $date = now()->subDays(rand(0, 59))->toDateString();
            $start = $hm[$u->id];
            $run = rand(4, 12);
            $hm[$u->id] = $start + $run;
            try {
                FleetService::recordMeterLog([
                    'equipment_id' => $u->id, 'log_date' => $date,
                    'shift_id' => $shifts->isNotEmpty() ? $shifts[$i % $shifts->count()]->id : null,
                    'hm_start' => $start, 'hm_end' => $hm[$u->id],
                    'operating_hours' => $run, 'idle_hours' => rand(0, 2),
                    'status' => 'IN_USE',
                    'operator_id' => $operators->isNotEmpty() ? $operators[$i % $operators->count()]->id : null,
                ]);
            } catch (\Throwable) {
            }
        }
        for ($i = 0; $i < 200; $i++) {
            $u = $units[($i * 7) % $units->count()];
            try {
                FleetService::recordInspection([
                    'equipment_id' => $u->id,
                    'inspection_date' => now()->subDays(rand(0, 59))->toDateString(),
                    'shift_id' => $shifts->isNotEmpty() ? $shifts[$i % $shifts->count()]->id : null,
                    'result' => $i % 10 === 0 ? 'FAIL' : 'PASS',
                    'findings' => $i % 10 === 0 ? 'Rem aus, perlu penggantian kampas.' : null,
                ]);
            } catch (\Throwable) {
            }
        }
        $this->command?->info('Fleet: ' . Equipment::count() . ' unit, ' . Vehicle::count() . ' kendaraan, ' . \App\Models\EquipmentMeterLog::count() . ' logs, ' . \App\Models\EquipmentInspection::count() . ' inspeksi');
    }

    protected function seedFuel($c1, $sites): void
    {
        $tanks = [];
        $n = 0;
        foreach ($sites->take(3) as $site) {
            foreach ([['SOLAR', 50000], ['SOLAR', 20000]] as [$fuel, $cap]) {
                $n++;
                $tanks[] = FuelTank::create([
                    'company_id' => $site->company_id, 'site_id' => $site->id,
                    'code' => 'TNK' . sprintf('%02d', $n), 'name' => 'Tangki ' . $fuel . ' ' . $site->code,
                    'capacity_liter' => $cap, 'fuel_type' => $fuel, 'status' => true, 'created_by' => 1,
                ]);
            }
        }
        while (count($tanks) < 5) {
            $n++;
            $tanks[] = FuelTank::create([
                'company_id' => $c1->id, 'site_id' => $sites->first()->id,
                'code' => 'TNK' . sprintf('%02d', $n), 'name' => 'Tangki Depot #' . $n,
                'capacity_liter' => 100000, 'fuel_type' => 'SOLAR', 'status' => true, 'created_by' => 1,
            ]);
        }
        $supplier = Supplier::first();
        // penerimaan besar dulu agar stok cukup
        foreach ($tanks as $tank) {
            $rc = \App\Models\FuelReceipt::create([
                'number' => NumberingService::generate('FUEL-R', $tank->company_id),
                'company_id' => $tank->company_id, 'site_id' => $tank->site_id,
                'fuel_tank_id' => $tank->id, 'supplier_id' => $supplier?->id,
                'receipt_date' => now()->subDays(60)->toDateString(),
                'liter' => 40000, 'unit_price' => 15000, 'total_cost' => 600000000,
                'status' => 'APPROVED', 'created_by' => 1,
            ]);
            try {
                FuelService::receive($rc->fresh());
            } catch (\Throwable) {
            }
        }
        // 500 issues
        $units = Equipment::orderBy('id')->get();
        $hm = [];
        foreach ($units as $u) {
            $hm[$u->id] = (float) ($u->meter_reading ?? 5000);
        }
        $posted = 0;
        for ($i = 0; $i < 500; $i++) {
            $tank = $tanks[$i % count($tanks)];
            $u = $units[$i % $units->count()];
            $useUnit = $i % 5 !== 0;
            $liter = rand(50, 400);
            $start = $hm[$u->id] ?? 5000;
            $run = rand(4, 10);
            if ($useUnit) {
                $hm[$u->id] = $start + $run;
            }
            $issue = \App\Models\FuelIssue::create([
                'number' => NumberingService::generate('FUEL', $tank->company_id),
                'company_id' => $tank->company_id, 'site_id' => $tank->site_id,
                'issue_date' => now()->subDays(rand(0, 59))->toDateString(),
                'fuel_tank_id' => $tank->id,
                'equipment_id' => $useUnit ? $u->id : null,
                'vehicle_plate' => $useUnit ? null : 'B ' . (1000 + ($i % 150)) . ' MN',
                'hm_before' => $useUnit ? $start : null,
                'hm_after' => $useUnit ? $hm[$u->id] : null,
                'liter' => $liter, 'status' => 'APPROVED', 'created_by' => 1,
            ]);
            try {
                FuelService::issue($issue->fresh());
                $posted++;
            } catch (\Throwable) {
            }
        }
        // transfer + dip
        try {
            $tr = \App\Models\FuelTransfer::create([                'number' => NumberingService::generate('FUEL-T'),
                'from_tank_id' => $tanks[0]->id, 'to_tank_id' => $tanks[1]->id,
                'transfer_date' => now()->subDays(5)->toDateString(), 'liter' => 5000,
                'status' => 'DRAFT', 'created_by' => 1,
            ]);
            FuelService::transfer($tr->fresh());
        } catch (\Throwable) {
        }
        for ($i = 0; $i < 10; $i++) {
            try {
                $tank = $tanks[$i % count($tanks)];
                $dip = new \App\Models\FuelTankDip([
                    'fuel_tank_id' => $tank->id, 'dip_date' => now()->subDays($i)->toDateString(),
                    'physical_liter' => max(FuelService::tankBalance($tank->id) + rand(-300, 100), 0),
                    'measured_by' => 1,
                ]);
                FuelService::dip($dip);
            } catch (\Throwable) {
            }
        }
        // perangkat timbangan (manual + REST push)
        $wbs = \App\Models\Weighbridge::orderBy('id')->limit(2)->get();
        foreach ($wbs as $idx => $wb) {
            \App\Models\WeighbridgeDevice::firstOrCreate(['code' => 'DEV-' . $wb->code], [
                'weighbridge_id' => $wb->id, 'name' => 'Bridge ' . $wb->code,
                'driver' => $idx === 0 ? 'MANUAL' : 'REST',
                'api_token' => $idx === 0 ? null : \Illuminate\Support\Str::random(60),
                'is_active' => true, 'created_by' => 1,
            ]);
        }
        $this->command?->info('Fuel: ' . count($tanks) . ' tangki, ' . $posted . ' issue POSTED');
    }

    protected function seedTires($c1): void
    {
        $brands = ['Bridgestone', 'Michelin', 'Goodyear'];
        $units = Equipment::where('type', 'DUMP_TRUCK')->orderBy('id')->limit(10)->get();
        for ($i = 1; $i <= 50; $i++) {
            $tire = \App\Models\Tire::create([
                'company_id' => $c1->id, 'serial_no' => 'TIRE' . sprintf('%04d', $i),
                'brand' => $brands[$i % 3], 'size' => '24.00-35', 'pattern' => 'E-4',
                'purchase_cost' => rand(80, 120) * 1000000, 'purchase_date' => now()->subDays(rand(30, 300))->toDateString(),
                'status' => 'STOCK', 'created_by' => 1,
            ]);
            if ($i <= 30 && $units->isNotEmpty()) {
                $u = $units[($i - 1) % $units->count()];
                try {
                    TireService::install($tire->id, $u->id, 'POS-' . (($i % 6) + 1), now()->subDays(rand(1, 20))->toDateString(), rand(5000, 20000));
                    if ($i % 7 === 0) {
                        TireService::repair($tire->id, now()->subDays(2)->toDateString(), 2500000, 'Tambal vulkanisir.');
                    }
                } catch (\Throwable) {
                }
            }
        }
        $this->command?->info('Tire: ' . \App\Models\Tire::count() . ' ban');
    }

    protected function seedDispatch($c1, $sites, $shifts): void
    {
        $trucks = Equipment::where('type', 'DUMP_TRUCK')->orderBy('id')->get();
        $loaders = Equipment::whereIn('type', ['EXCAVATOR', 'LOADER'])->orderBy('id')->get();
        $drivers = Employee::where('status', 'ACTIVE')->orderBy('id')->limit(60)->get();
        $lps = \App\Models\LoadingPoint::orderBy('id')->get();
        $dps = \App\Models\DumpingPoint::orderBy('id')->get();
        $routes = \App\Models\HaulingRoute::orderBy('id')->get();
        if ($trucks->isEmpty() || $lps->isEmpty() || $dps->isEmpty()) {
            // master dispatch minimal
            $site = $sites->first();
            $pit = Pit::first();
            foreach (['LP-A', 'LP-B'] as $j => $code) {
                $lps->push(\App\Models\LoadingPoint::firstOrCreate(['code' => $code], [
                    'site_id' => $site->id, 'pit_id' => $pit?->id, 'name' => 'Front ' . $code, 'status' => true, 'created_by' => 1,
                ]));
            }
            foreach ([['DP-ROM', 'STOCKPILE'], ['DP-CRS', 'CRUSHER']] as [$code, $type]) {
                $dps->push(\App\Models\DumpingPoint::firstOrCreate(['code' => $code], [
                    'site_id' => $site->id, 'name' => $code, 'type' => $type, 'status' => true, 'created_by' => 1,
                ]));
            }
            $routes->push(\App\Models\HaulingRoute::firstOrCreate(['code' => 'RT-01'], [
                'site_id' => $site->id, 'name' => 'PIT ke ROM', 'loading_point_id' => $lps[0]->id,
                'dumping_point_id' => $dps[0]->id, 'distance_km' => 3.5, 'status' => true, 'created_by' => 1,
            ]));
        }
        $made = 0;
        for ($i = 0; $i < 500; $i++) {
            $truck = $trucks[$i % max($trucks->count(), 1)] ?? null;
            if (!$truck) {
                break;
            }
            $site = $sites[$i % $sites->count()];
            $shift = $shifts->isNotEmpty() ? $shifts[$i % $shifts->count()] : null;
            // hindari dobel truk+shift+tanggal: geser tanggal bila bentrok
            $date = now()->subDays((int) ($i / max($trucks->count(), 1)) % 30)->toDateString();
            try {
                $trip = DispatchService::assign([
                    'company_id' => $site->company_id, 'site_id' => $site->id,
                    'trip_date' => $date, 'shift_id' => $shift?->id,
                    'truck_id' => $truck->id,
                    'driver_id' => $drivers->isNotEmpty() ? $drivers[$i % $drivers->count()]->id : null,
                    'loader_id' => $loaders->isNotEmpty() ? $loaders[$i % $loaders->count()]->id : null,
                    'pit_id' => Pit::where('site_id', $site->id)->value('id'),
                    'loading_point_id' => $lps[$i % $lps->count()]->id,
                    'dumping_point_id' => $dps[$i % $dps->count()]->id,
                    'hauling_route_id' => $routes->isNotEmpty() ? $routes[$i % $routes->count()]->id : null,
                ]);
                $made++;
                // sebagian dijalankan fasenya agar cycle time terisi
                if ($i % 3 === 0) {
                    foreach (['LOADING', 'HAULING', 'DUMPED', 'COMPLETED'] as $phase) {
                        try {
                            DispatchService::stamp($trip->fresh(), $phase);
                        } catch (\Throwable) {
                            break;
                        }
                    }
                    $trip->fresh()->update(['tonnage' => rand(25, 60)]);
                }
            } catch (\Throwable) {
            }
        }
        $this->command?->info('Dispatch: ' . $made . ' trip');
    }

    protected function seedStockpile($c1, $sites): void
    {
        $items = Item::whereIn('type', ['PRODUCT', 'RAW'])->orderBy('id')->limit(4)->get();
        if ($items->isEmpty()) {
            return;
        }
        $n = 0;
        foreach ($sites->take(5) as $site) {
            foreach ($items->take(2) as $item) {
                $n++;
                if ($n > 10) {
                    break 2;
                }
                $wh = Warehouse::where('site_id', $site->id)->first();
                $pile = Stockpile::firstOrCreate(['code' => 'SP' . sprintf('%02d', $n)], [
                    'company_id' => $site->company_id, 'site_id' => $site->id,
                    'name' => 'ROM ' . $site->code . ' ' . $item->code,
                    'item_id' => $item->id, 'warehouse_id' => $wh?->id,
                    'capacity_ton' => 50000, 'survey_threshold_pct' => 3,
                    'status' => true, 'created_by' => 1,
                ]);
                try {
                    StockpileService::move($pile->id, 'OPENING', 10000 + rand(0, 5000), 0, null, 'OPENING', 'SEED', now()->subDays(60)->toDateString(), 'Saldo awal demo');
                    StockpileService::move($pile->id, 'PRODUCTION_IN', rand(2000, 8000), 0, null, 'DEMO', 'SEED', now()->subDays(30)->toDateString(), 'Produksi demo');
                } catch (\Throwable) {
                }
                // survei (sebagianಿನ INVESTIGATE agar alur terlihat)
                for ($s = 0; $s < 2; $s++) {
                    try {
                        $bal = StockpileService::balance($pile->id);
                        $survey = StockpileService::survey($pile->id, now()->subDays($s === 0 ? 7 : 1)->toDateString(), round($bal * (1 + ($s === 0 ? 0.05 : 0.005)), 2), 'Surveyor Demo');
                        if ($survey->status === 'PENDING') {
                            StockpileService::approveSurvey($survey, 'Selisih wajar.');
                        }
                    } catch (\Throwable) {
                    }
                }
            }
        }
        $this->command?->info('Stockpile: ' . Stockpile::count() . ' pile, ' . \App\Models\StockpileSurvey::count() . ' survei');
    }

    protected function seedQuality(): void
    {
        foreach ([
            ['TM', 'Total Moisture', '%'], ['TS', 'Total Sulphur', '%'],
            ['ASH', 'Ash Content', '%'], ['CV', 'Calorific Value', 'kcal/kg'],
        ] as [$code, $name, $unit]) {
            QualityParameter::firstOrCreate(['code' => $code], ['name' => $name, 'unit' => $unit, 'status' => true, 'created_by' => 1]);
        }
        $batch = \App\Models\ProductionBatch::first();
        for ($i = 0; $i < 50; $i++) {
            try {
                $sample = QualityService::createSample([
                    'source_type' => 'PRODUCTION_BATCH', 'source_id' => $batch?->id ?? 1,
                    'item_id' => Item::whereIn('type', ['PRODUCT', 'RAW'])->value('id'),
                    'sample_date' => now()->subDays(rand(0, 30))->toDateString(),
                ]);
                foreach (QualityParameter::limit(3)->get() as $p) {
                    try {
                        QualityService::recordTest($sample->id, $p->id, match ($p->code) {
                            'TM' => rand(80, 140) / 10, 'TS' => rand(5, 25) / 10,
                            'ASH' => rand(30, 90) / 10, default => rand(5000, 6500),
                        });
                    } catch (\Throwable) {
                    }
                }
            } catch (\Throwable) {
            }
        }
        $this->command?->info('Quality: ' . \App\Models\QcSample::count() . ' sampel');
    }

    protected function seedContracts($c1, $sites): void
    {
        $customer = Customer::first();
        $supplier = Supplier::first();
        $product = Item::where('type', 'PRODUCT')->first();
        $term = \App\Models\PaymentTerm::first();
        for ($i = 0; $i < 10 && $customer && $product; $i++) {
            \App\Models\CustomerContract::firstOrCreate(['number' => 'CTR-C-DEMO' . sprintf('%02d', $i + 1)], [
                'company_id' => $c1->id, 'customer_id' => $customer->id, 'item_id' => $product->id,
                'site_id' => $sites->first()->id, 'contract_qty' => 50000, 'price' => 850000,
                'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(),
                'payment_term_id' => $term?->id, 'status' => 'ACTIVE', 'created_by' => 1,
            ]);
        }
        for ($i = 0; $i < 6 && $supplier; $i++) {
            \App\Models\SupplierContract::firstOrCreate(['number' => 'CTR-S-DEMO' . sprintf('%02d', $i + 1)], [
                'company_id' => $c1->id, 'supplier_id' => $supplier->id,
                'service_description' => 'Jasa demo ' . ($i + 1), 'contract_value' => 1000000000,
                'price' => 1000000000, 'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(), 'status' => 'ACTIVE', 'created_by' => 1,
            ]);
        }
        $route = \App\Models\HaulingRoute::first();
        for ($i = 0; $i < 4 && $supplier; $i++) {
            \App\Models\HaulingContract::firstOrCreate(['number' => 'CTR-H-DEMO' . sprintf('%02d', $i + 1)], [
                'company_id' => $c1->id, 'supplier_id' => $supplier->id,
                'hauling_route_id' => $route?->id, 'rate_type' => 'PER_TON', 'rate' => 45000,
                'minimum_volume' => 10000, 'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(), 'status' => 'ACTIVE', 'created_by' => 1,
            ]);
        }
        $this->command?->info('Contract: ' . \App\Models\CustomerContract::count() . '/' . \App\Models\SupplierContract::count() . '/' . \App\Models\HaulingContract::count());
    }

    protected function seedBudget($c1, $sites): void
    {
        $coas = \App\Models\ChartOfAccount::whereIn('type', ['EXPENSE', 'ASSET'])->where('is_postable', true)->orderBy('code')->limit(8)->get();
        if ($coas->isEmpty()) {
            return;
        }
        $budget = \App\Models\Budget::firstOrCreate(
            ['company_id' => $c1->id, 'year' => now()->year, 'type' => 'OPEX'],
            ['number' => NumberingService::generate('BGT', $c1->id), 'site_id' => null, 'status' => 'APPROVED', 'version' => 1, 'created_by' => 1]
        );
        foreach ($coas as $coa) {
            $budget->lines()->firstOrCreate(
                ['chart_of_account_id' => $coa->id, 'period' => null],
                ['amount' => 1200000000]
            );
        }
        $this->command?->info('Budget: ' . \App\Models\Budget::count() . ' budget, ' . \App\Models\BudgetLine::count() . ' lines');
    }

    protected function seedHse($c1, $sites, $operators): void
    {
        $kinds = ['INCIDENT', 'NEAR_MISS', 'HAZARD'];
        $units = Equipment::orderBy('id')->limit(20)->get();
        for ($i = 0; $i < 20; $i++) {
            try {
                $report = HseService::report([
                    'company_id' => $c1->id, 'site_id' => $sites[$i % $sites->count()]->id,
                    'kind' => $kinds[$i % 3], 'location' => 'Pit ' . chr(65 + ($i % 3)),
                    'occurred_at' => now()->subDays(rand(0, 60))->toDateTimeString(),
                    'employee_id' => $operators->isNotEmpty() ? $operators[$i % $operators->count()]->id : null,
                    'equipment_id' => $units->isNotEmpty() ? $units[$i % $units->count()]->id : null,
                    'severity' => ['LOW', 'MEDIUM', 'HIGH'][$i % 3],
                    'description' => 'Kasus demo HSE #' . ($i + 1),
                ]);
                if ($i % 2 === 0 && $operators->isNotEmpty()) {
                    HseService::investigate($report->fresh(), 'Root cause demo: prosedur kurang dipatuhi.');
                    $action = HseService::addAction($report->id, [
                        'action' => 'Refresher training prosedur.',
                        'responsible_id' => $operators[$i % $operators->count()]->id,
                        'due_date' => now()->addDays(14)->toDateString(),
                    ]);
                    if ($i % 4 === 0) {
                        HseService::closeAction($action->fresh(), 'Training selesai, daftar hadir terlampir.');
                        HseService::verifyAction($action->fresh());
                        HseService::closeReport($report->fresh());
                    }
                }
            } catch (\Throwable) {
            }
        }
        $this->command?->info('HSE: ' . \App\Models\HseReport::count() . ' laporan');
    }

    protected function seedCompliance($c1, $sites, $operators): void
    {
        $types = ['PERMIT', 'LICENSE', 'EMP_CERT', 'EQUIP_CERT', 'ENVIRONMENT', 'CONTRACT', 'OTHER'];
        $units = Equipment::orderBy('id')->limit(10)->get();
        for ($i = 0; $i < 20; $i++) {
            try {
                ComplianceService::register([
                    'type' => $types[$i % 7], 'title' => 'Dokumen compliance demo #' . ($i + 1),
                    'document_number' => 'DOC/2026/' . sprintf('%03d', $i + 1),
                    'company_id' => $c1->id, 'site_id' => $sites[$i % $sites->count()]->id,
                    'employee_id' => ($i % 7 === 2 && $operators->isNotEmpty()) ? $operators[$i % $operators->count()]->id : null,
                    'equipment_id' => ($i % 7 === 3 && $units->isNotEmpty()) ? $units[$i % $units->count()]->id : null,
                    'issued_date' => now()->subDays(300)->toDateString(),
                    'expiry_date' => now()->addDays([-40, -5, 5, 12, 25, 55, 80, 120][$i % 8])->toDateString(),
                    'responsible_id' => $operators->isNotEmpty() ? $operators[$i % $operators->count()]->id : null,
                ]);
            } catch (\Throwable) {
            }
        }
        // refresh status expiry
        try {
            ComplianceService::dispatchReminders();
        } catch (\Throwable) {
        }
        $this->command?->info('Compliance: ' . \App\Models\ComplianceRegister::count() . ' register');
    }
}
