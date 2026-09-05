<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Site;
use App\Models\VendorBill;
use App\Models\Weighbridge;
use App\Models\WorkOrder;
use App\Services\AccountingService;
use App\Services\DepositService;
use App\Services\MaintenanceService;
use App\Services\NumberingService;
use App\Services\ProcurementService;
use App\Services\ProductionService;
use App\Services\PayrollService;
use App\Services\PriceService;
use App\Services\SalesService;
use App\Models\ProductionBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesOrder;
use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Production: demo transaksi diblokir.');
            return;
        }
        if (SalesOrder::count() > 0) {
            $this->command?->info('Transaksi demo sudah ada — dilewati.');
            return;
        }

        AuditService::disable();

        // login as super admin for created_by
        Auth::loginUsingId(1);

        $company = Company::where('code', 'MIN1')->first();
        $sites = Site::whereIn('code', ['S-BJM', 'S-KTN'])->get();
        [$s1, $s2] = $sites;
        $wh1 = $s1->warehouses()->first();
        $wh2 = $s2->warehouses()->first();
        $crusher = \App\Models\Crusher::where('site_id', $s2->id)->first() ?? \App\Models\Crusher::first();
        $crusherWh = $wh2; // product output lands in crusher's stockpile (wh2 in this seed scenario)
        $wb = Weighbridge::first();
        $raw = Item::where('code', 'BAT-BARU')->first();
        $product = Item::where('code', 'BST-1-2')->first();
        $excavator = Equipment::where('type', 'EXCAVATOR')->first();
        $truck = Equipment::where('type', 'DUMP_TRUCK')->first();
        $operator = Employee::where('position', 'like', '%Operator Excavator%')->first() ?? Employee::first();
        $customers = Customer::where('company_id', $company->id)->limit(10)->get();

        // ================= MINING ACTIVITIES (7 hari) =================
        for ($d = 13; $d >= 0; $d--) {
            $date = today()->subDays($d);
            if ($date->isSunday()) {
                continue;
            }
            $tonnage = rand(800, 2000);
            $activity = \App\Models\MiningActivity::create([
                'number' => NumberingService::generate('MA', $company->id),
                'company_id' => $company->id,
                'site_id' => $d % 2 ? $s1->id : $s2->id,
                'date' => $date,
                'activity_type' => 'MINING',
                'equipment_id' => $excavator->id,
                'operator_id' => $operator->id,
                'item_id' => $raw->id,
                'target_warehouse_id' => $d % 2 ? $wh1->id : $wh2->id,
                'quantity' => rand(30, 60),
                'tonnage' => $tonnage,
                'working_hours' => rand(6, 10),
                'status' => 'DRAFT',
                'created_by' => 1,
            ]);
            $activity->update(['status' => 'APPROVED', 'approved_by' => 1, 'approved_at' => $date]);

            // post → stock in
            StockService::move(
                $activity->target_warehouse_id,
                $raw->id,
                'PRODUCTION',
                $tonnage,
                0,
                $company->id,
                $activity->site_id,
                $activity->id,
                'MINING_ACTIVITY',
                $activity->number,
                90000,
                $date->toDateString()
            );
            $activity->update(['status' => 'POSTED', 'posted_by' => 1, 'posted_at' => $date]);
        }

        // ================= PRODUCTION BATCH =================
        $batch = ProductionBatch::create([
            'number' => NumberingService::generate('PB', $company->id),
            'company_id' => $company->id,
            'site_id' => $s2->id,
            'crusher_id' => $crusher->id,
            'date' => today()->subDay(),
            'operator_id' => $operator->id,
            'start_time' => today()->subDay()->setTime(7, 0),
            'finish_time' => today()->subDay()->setTime(15, 0),
            'status' => 'DRAFT',
            'created_by' => 1,
        ]);
        $batch->inputs()->create(['item_id' => $raw->id, 'warehouse_id' => $wh2->id, 'tonnage' => 3000]);
        $batch->outputs()->create(['item_id' => $product->id, 'warehouse_id' => $wh2->id, 'gross_tonnage' => 2650, 'net_tonnage' => 2650]);
        $batch->losses()->create(['category' => 'DEBU', 'tonnage' => 150]);
        $batch->losses()->create(['category' => 'MOISTURE', 'tonnage' => 100]);
        $batch->update([
            'input_tonnage' => 3000,
            'gross_output' => 2650,
            'total_loss' => 250,
            'total_scrap' => 0,
            'net_output' => 2650,
            'status' => 'APPROVED',
            'approved_by' => 1,
        ]);
        ProductionService::post($batch);

        // ================= OPERATOR INCENTIVE =================
        $incentive = \App\Models\OperatorIncentive::create([
            'number' => NumberingService::generate('INC'),
            'employee_id' => $operator->id,
            'site_id' => $s1->id,
            'basis' => 'TONNAGE',
            'quantity' => 15000,
            'rate' => 350,
            'amount' => 5250000,
            'period' => now()->format('Y-m'),
            'status' => 'APPROVED',
            'approved_by' => 1,
            'created_by' => 1,
        ]);

        // ================= CUSTOMER DEPOSIT =================
        $vip = $customers->first();
        DepositService::depositIn($company->id, $vip->id, 200000000, today()->subDays(10), null, 'DEP-VIP-001', 'Deposit awal kontrak');

        // ================= SALES: SO → DO → TIMBANG → INVOICE → PAYMENT =================
        foreach ($customers->take(6) as $i => $customer) {
            $so = SalesOrder::create([
                'number' => NumberingService::generate('SO', $company->id),
                'company_id' => $company->id,
                'site_id' => $s1->id,
                'customer_id' => $customer->id,
                'order_date' => today()->subDays(7 - $i),
                'status' => 'DRAFT',
                'created_by' => 1,
            ]);
            $qty = rand(220, 400);
            $price = PriceService::resolvePrice($customer, $s1->id, $product->id);
            $so->items()->create(['item_id' => $product->id, 'qty' => $qty, 'qty_delivered' => 0, 'unit_price' => $price, 'total_price' => $qty * $price]);
            $so->update(['subtotal' => $qty * $price, 'tax_amount' => round($qty * $price * 0.11), 'total' => round($qty * $price * 1.11), 'status' => 'APPROVED', 'approved_by' => 1]);

            // DO + weighbridge + complete
            $do = \App\Models\DeliveryOrder::create([
                'number' => NumberingService::generate('DO', $company->id),
                'sales_order_id' => $so->id,
                'warehouse_id' => $crusherWh->id,
                'delivery_date' => today()->subDays(6 - $i),
                'vehicle_plate' => 'B ' . (9000 + $i) . ' MT',
                'driver_name' => 'Supir ' . ($i + 1),
                'total_qty' => $qty,
                'status' => 'DRAFT',
                'created_by' => 1,
            ]);
            $do->items()->create(['item_id' => $product->id, 'qty_ordered' => $qty]);

            $net = $qty - rand(0, 20); // selisih kecil realistis
            $ticket = \App\Models\WeighbridgeTicket::create([
                'ticket_no' => NumberingService::generate('WB', $company->id, $s1->id),
                'weighbridge_id' => $wb->id,
                'company_id' => $company->id,
                'site_id' => $s1->id,
                'direction' => 'OUT',
                'first_weigh_at' => today()->subDays(6 - $i)->setTime(8, 0),
                'second_weigh_at' => today()->subDays(6 - $i)->setTime(9, 30),
                'first_weight' => $net + rand(15000, 18000),
                'second_weight' => rand(15000, 18000),
                'gross' => $net + 16000,
                'tare' => 16000,
                'net' => $net,
                'vehicle_plate' => $do->vehicle_plate,
                'driver_name' => $do->driver_name,
                'customer_id' => $customer->id,
                'item_id' => $product->id,
                'warehouse_id' => $crusherWh->id,
                'calibration_version' => 'CAL-2026-' . $wb->id,
                'operator_id' => 1,
                'status' => 'COMPLETE',
                'created_by' => 1,
            ]);

            try {
                SalesService::completeDelivery($do, $ticket);
            } catch (\Throwable $e) {
                $this->command?->warn('DO gagal: ' . $e->getMessage());
                continue;
            }

            // invoice
            try {
                $invoice = SalesService::createInvoice($so, today()->subDays(5 - $i), null, $customer->id === $vip->id);
            } catch (\Throwable $e) {
                $this->command?->warn('Invoice gagal: ' . $e->getMessage());
            }

            // payment untuk sebagian customer (bukan deposit)
            if ($i >= 2 && isset($invoice)) {
                try {
                    SalesService::receivePayment($company->id, $customer->id, (float) $invoice->total, today()->subDays(1), 'BANK_TRANSFER', null, 'PAY-' . $invoice->number, [$invoice->id]);
                } catch (\Throwable $e) {
                    $this->command?->warn('Payment gagal: ' . $e->getMessage());
                }
            }
        }

        // ================= PROCUREMENT: PR → PO → GRN → BILL =================
        $spare = Item::where('code', 'FLT-OIL')->first();
        $supplier = \App\Models\Supplier::where('company_id', $company->id)->first();

        $pr = PurchaseRequest::create([
            'number' => NumberingService::generate('PR', $company->id),
            'company_id' => $company->id,
            'site_id' => $s1->id,
            'request_date' => today()->subDays(10),
            'status' => 'DRAFT',
            'created_by' => 1,
        ]);
        $pr->items()->create(['item_id' => $spare->id, 'qty' => 50]);
        $pr->update(['status' => 'APPROVED', 'approved_by' => 1]);

        $po = PurchaseOrder::create([
            'number' => NumberingService::generate('PO', $company->id),
            'company_id' => $company->id,
            'site_id' => $s1->id,
            'supplier_id' => $supplier->id,
            'purchase_request_id' => $pr->id,
            'order_date' => today()->subDays(9),
            'status' => 'DRAFT',
            'created_by' => 1,
        ]);
        $po->items()->create(['item_id' => $spare->id, 'qty' => 50, 'unit_price' => 820000, 'total_price' => 41000000]);
        $po->update(['subtotal' => 41000000, 'tax_amount' => 4510000, 'total' => 45510000, 'status' => 'APPROVED', 'approved_by' => 1]);

        $grn = GoodsReceipt::create([
            'number' => NumberingService::generate('GRN', $company->id),
            'purchase_order_id' => $po->id,
            'warehouse_id' => \App\Models\Warehouse::where('code', 'WSPARE')->first()->id,
            'receipt_date' => today()->subDays(7),
            'status' => 'DRAFT',
            'qc_status' => 'PASSED',
            'created_by' => 1,
        ]);
        $grn->items()->create(['item_id' => $spare->id, 'qty_received' => 50, 'qty_accepted' => 50, 'unit_cost' => 820000]);
        ProcurementService::postGoodsReceipt($grn);

        $bill = VendorBill::create([
            'number' => NumberingService::generate('BILL'),
            'supplier_invoice_no' => 'INV-SUP-' . rand(1000, 9999),
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'bill_date' => today()->subDays(6),
            'due_date' => today()->addDays(24),
            'subtotal' => 41000000,
            'tax_amount' => 4510000,
            'total' => 45510000,
            'status' => 'DRAFT',
            'created_by' => 1,
        ]);
        $bill->items()->create(['item_id' => $spare->id, 'qty' => 50, 'unit_price' => 820000, 'total_price' => 41000000]);
        try {
            ProcurementService::postVendorBill($bill);
        } catch (\Throwable $e) {
            $this->command?->warn('Bill gagal: ' . $e->getMessage());
        }

        // ================= MAINTENANCE: WO + SPAREPART ISSUE =================
        $wo = WorkOrder::create([
            'number' => NumberingService::generate('WO', $company->id),
            'company_id' => $company->id,
            'site_id' => $s1->id,
            'equipment_id' => $excavator->id,
            'type' => 'PREVENTIVE',
            'priority' => 'NORMAL',
            'date' => today()->subDays(5),
            'description' => 'Ganti oli & filter berkala 250 jam',
            'estimated_cost' => 2000000,
            'status' => 'DRAFT',
            'created_by' => 1,
        ]);
        $wo->tasks()->create(['description' => 'Drain oli lama', 'is_done' => true]);
        $wo->tasks()->create(['description' => 'Pasang filter baru', 'is_done' => true]);
        $wo->parts()->create(['item_id' => $spare->id, 'warehouse_id' => \App\Models\Warehouse::where('code', 'WSPARE')->first()->id, 'qty' => 2, 'unit_cost' => 820000]);
        $wo->update(['status' => 'APPROVED', 'approved_by' => 1]);

        $part = $wo->parts()->first();
        try {
            MaintenanceService::issuePart($part);
            $wo->update(['status' => 'COMPLETED', 'actual_finish' => now(), 'actual_cost' => $wo->costs()->sum('amount')]);
        } catch (\Throwable $e) {
            $this->command?->warn('Part issue gagal: ' . $e->getMessage());
        }

        // ================= PAYROLL =================
        $payroll = \App\Models\PayrollRun::create([
            'number' => NumberingService::generate('PYR', $company->id),
            'company_id' => $company->id,
            'period' => now()->subMonth()->format('Y-m'),
            'status' => 'DRAFT',
            'created_by' => 1,
        ]);
        try {
            PayrollService::calculate($payroll);
            $payroll->update(['status' => 'APPROVED', 'approved_by' => 1]);
            PayrollService::post($payroll);
        } catch (\Throwable $e) {
            $this->command?->warn('Payroll gagal: ' . $e->getMessage());
        }

        // ================= OPENING JOURNAL (modal) =================
        AccountingService::post($company->id, today()->subMonth()->toDateString(), [
            ['code' => '1-1000', 'debit' => 2000000000, 'memo' => 'Setoran modal kas'],
            ['code' => '1-1100', 'debit' => 5000000000, 'memo' => 'Setoran modal bank'],
            ['code' => '3-1000', 'credit' => 7000000000, 'memo' => 'Modal disetor'],
        ], 'OPENING', null, 'OPENING-BALANCE', 'Saldo awal perusahaan', 'JN');

        Auth::logout();
        AuditService::enable();

        $this->command?->info('Transaksi demo: MA=' . \App\Models\MiningActivity::count() . ' WB=' . \App\Models\WeighbridgeTicket::count() . ' SO=' . SalesOrder::count() . ' INV=' . Invoice::count() . ' PO=' . PurchaseOrder::count() . ' WO=' . WorkOrder::count() . ' Journals=' . \App\Models\JournalEntry::count());
    }
}
