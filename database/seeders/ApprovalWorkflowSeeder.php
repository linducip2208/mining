<?php

namespace Database\Seeders;

use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Services\ApprovalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflows = [
            // Matriks nominal PR sesuai kebijakan contoh (configurable via steps)
            ['PR-APPROVAL', 'Approval Permintaan Pembelian', 'PROCUREMENT', 'PURCHASE_REQUEST', [
                ['Persetujuan Site Manager (< Rp 10 Jt)', 'SITE_MANAGER', null, 0, 10000000],
                ['Persetujuan General Manager (Rp 10–100 Jt)', 'GENERAL_MANAGER', null, 10000001, 100000000],
                ['Persetujuan Direktur (> Rp 100 Jt)', 'DIRECTOR', null, 100000001, null],
            ]],
            ['PAYROLL-APPROVAL', 'Approval Payroll', 'PAYROLL', 'PAYROLL_RUN', [
                ['Persetujuan Finance Manager', 'FINANCE_MANAGER', null, 0, null],
            ]],
            ['WB-VOID-APPROVAL', 'Approval Void Timbangan', 'WEIGHBRIDGE', 'WEIGHBRIDGE_VOID', [
                ['Persetujuan Site Manager', 'SITE_MANAGER', null, 0, null],
            ]],
            ['PRICE-APPROVAL', 'Approval Daftar Harga', 'PRICE', 'PRICE_LIST', [
                ['Persetujuan Sales Manager', 'SALES_MANAGER', null, 0, null],
            ]],
            ['PB-APPROVAL', 'Approval Batch Produksi', 'PRODUCTION', 'PRODUCTION_BATCH', [
                ['Persetujuan Production Manager', 'PRODUCTION_MANAGER', null, 0, null],
            ]],
            ['ADJ-APPROVAL', 'Approval Penyesuaian Stok', 'STOCK', 'STOCK_ADJUSTMENT', [
                ['Persetujuan Warehouse Manager', 'WAREHOUSE_MANAGER', null, 0, null],
            ]],
        ];

        foreach ($workflows as [$code, $name, $module, $type, $steps]) {
            $wf = ApprovalWorkflow::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'module' => $module, 'transaction_type' => $type, 'is_active' => true, 'created_by' => 1]
            );
            $seq = 1;
            foreach ($steps as [$stepName, $roleCode, $userId, $min, $max]) {
                $role = Role::where('code', $roleCode)->first();
                $wf->steps()->firstOrCreate(
                    ['approval_workflow_id' => $wf->id, 'sequence' => $seq],
                    ['name' => $stepName, 'role_id' => $role?->id, 'user_id' => $userId, 'min_amount' => $min, 'max_amount' => $max, 'mode' => 'SEQUENCE']
                );
                $seq++;
            }
        }

        $this->command?->info('Approval workflows: ' . ApprovalWorkflow::count() . ' workflows');

        // Demo: ajukan 1 PR nyata lewat engine → PENDING approval + notifikasi
        if (ApprovalRequest::where('transaction_type', 'PURCHASE_REQUEST')->exists()) {
            return;
        }

        $purchasing = \App\Models\User::where('username', 'purchasing')->first();
        $company = \App\Models\Company::where('code', 'MIN1')->first();
        $site = \App\Models\Site::where('code', 'S-BJM')->first();
        $spare = \App\Models\Item::where('code', 'FLT-OIL')->first();

        if (!$purchasing || !$company || !$spare) {
            return;
        }

        Auth::login($purchasing);
        $pr = PurchaseRequest::create([
            'number' => \App\Services\NumberingService::generate('PR', $company->id),
            'company_id' => $company->id,
            'site_id' => $site?->id,
            'request_date' => today(),
            'required_date' => today()->addDays(14),
            'notes' => 'Permintaan demo: filter oli untuk PM excavator (rutin)',
            'status' => 'DRAFT',
            'created_by' => $purchasing->id,
        ]);
        $pr->items()->create(['item_id' => $spare->id, 'qty' => 4, 'remark' => 'PM 250 jam']);

        $request = ApprovalService::submit('PROCUREMENT', 'PURCHASE_REQUEST', $pr);
        Auth::logout();

        $this->command?->info('Demo PR submitted: ' . $pr->number . ' → ' . ($request?->number ?? 'auto-approved'));
    }
}
