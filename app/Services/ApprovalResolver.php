<?php

namespace App\Services;

use App\Models\ApprovalRequest;

/**
 * Maps an ApprovalRequest to its underlying transaction model so the
 * central ApprovalEngine can update statuses without module-specific logic.
 */
class ApprovalResolver
{
    protected array $map = [
        'PURCHASE_REQUEST' => \App\Models\PurchaseRequest::class,
        'PURCHASE_ORDER' => \App\Models\PurchaseOrder::class,
        'SALES_ORDER' => \App\Models\SalesOrder::class,
        'PRICE_LIST' => \App\Models\PriceList::class,
        'PAYROLL_RUN' => \App\Models\PayrollRun::class,
        'STOCK_ADJUSTMENT' => \App\Models\StockAdjustment::class,
        'STOCK_TRANSFER' => \App\Models\StockTransfer::class,
        'WORK_ORDER' => \App\Models\WorkOrder::class,
        'CSR_PROGRAM' => \App\Models\CsrProgram::class,
        'PRICE_VARIANCE' => \App\Models\PriceVariance::class,
        'LEAVE' => \App\Models\Leave::class,
        'OVERTIME' => \App\Models\Overtime::class,
        'OPERATOR_INCENTIVE' => \App\Models\OperatorIncentive::class,
        'DOCUMENT' => \App\Models\Document::class,
        'MINING_ACTIVITY' => \App\Models\MiningActivity::class,
        'PRODUCTION_BATCH' => \App\Models\ProductionBatch::class,
        'WEIGHBRIDGE_VOID' => \App\Models\WeighbridgeTicket::class,
        'FUEL_ISSUE' => \App\Models\FuelIssue::class,
        'FUEL_RECEIPT' => \App\Models\FuelReceipt::class,
        'STOCKPILE_SURVEY' => \App\Models\StockpileSurvey::class,
        'BUDGET' => \App\Models\Budget::class,
        'CUSTOMER_CONTRACT' => \App\Models\CustomerContract::class,
        'SUPPLIER_CONTRACT' => \App\Models\SupplierContract::class,
        'HAULING_CONTRACT' => \App\Models\HaulingContract::class,
        'MINING_COST' => \App\Models\MiningOtherCost::class,
        'HSE_PERMIT' => \App\Models\HsePermit::class,
        'HSE_CLOSE' => \App\Models\HseReport::class,
        'QUALITY_SPECIAL' => \App\Models\QualityHold::class,
    ];

    public function resolve(ApprovalRequest $request)
    {
        $class = $this->map[$request->transaction_type] ?? null;
        if (!$class) {
            return null;
        }
        return $class::find($request->transaction_id);
    }

    public function applyStatus(ApprovalRequest $request, string $finalStatus): void
    {
        $txn = $this->resolve($request);
        if (!$txn) {
            return;
        }
        // side-effect handlers per tipe transaksi baru
        if ($finalStatus === 'APPROVED') {
            match ($request->transaction_type) {
                'STOCKPILE_SURVEY' => $this->approveSurvey($request, $txn),
                'QUALITY_SPECIAL' => $this->approveQualitySpecial($request, $txn),
                'HSE_CLOSE' => $this->approveHseClose($request, $txn),
                'CUSTOMER_CONTRACT', 'SUPPLIER_CONTRACT', 'HAULING_CONTRACT' => $this->approveContract($request, $txn),
                default => $this->approveDefault($request, $txn),
            };
            return;
        }
        $statusMap = [
            'REJECTED' => 'REJECTED',
            'RETURNED' => 'DRAFT',
            'CANCELLED' => 'CANCELLED',
        ];
        $new = $statusMap[$finalStatus] ?? $finalStatus;
        if (array_key_exists('status', $txn->getAttributes()) || $txn->isFillable('status')) {
            $txn->status = $new;
            $txn->save();
            AuditService::log('REJECT', $request->module, $txn->id, $txn::class, null, ['status' => $new]);
        }
    }

    protected function lastApprovalNotes(ApprovalRequest $request): ?string
    {
        return $request->actions()->where('action', 'APPROVE')->orderByDesc('sequence')->first()?->notes;
    }

    protected function lastApproverId(ApprovalRequest $request): ?int
    {
        return $request->actions()->where('action', 'APPROVE')->orderByDesc('sequence')->first()?->approver_id;
    }

    protected function approveDefault(ApprovalRequest $request, $txn): void
    {
        $new = 'APPROVED';
        if (array_key_exists('status', $txn->getAttributes()) || $txn->isFillable('status')) {
            $txn->status = $new;
            if (in_array('approved_by', $txn->getFillable()) && $new === 'APPROVED') {
                $txn->approved_by = auth()->id();
            }
            $txn->save();
            AuditService::log('APPROVE', $request->module, $txn->id, $txn::class, null, ['status' => $new]);
        }
    }

    protected function approveSurvey(ApprovalRequest $request, $survey): void
    {
        $notes = $this->lastApprovalNotes($request) ?? $survey->investigation;
        \App\Services\StockpileService::approveSurvey($survey, $notes);
    }

    protected function approveQualitySpecial(ApprovalRequest $request, $hold): void
    {
        \App\Services\QualityService::specialApprove(
            $hold,
            $this->lastApprovalNotes($request) ?? 'Special approval via approval center',
            $this->lastApproverId($request)
        );
    }

    protected function approveHseClose(ApprovalRequest $request, $report): void
    {
        \App\Services\HseService::closeReport($report);
    }

    protected function approveContract(ApprovalRequest $request, $contract): void
    {
        // kontrak aktif (ACTIVE), bukan APPROVED generik
        $contract->status = 'ACTIVE';
        if ($contract->isFillable('approved_by')) {
            $contract->approved_by = $this->lastApproverId($request) ?? auth()->id();
        }
        $contract->save();
        AuditService::log('APPROVE', $request->module, $contract->id, $contract::class, null, ['status' => 'ACTIVE']);
    }
}
