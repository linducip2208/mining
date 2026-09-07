<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\Budget;
use App\Models\CsrProgram;
use App\Models\CustomerContract;
use App\Models\Document;
use App\Models\FuelIssue;
use App\Models\FuelReceipt;
use App\Models\HaulingContract;
use App\Models\HsePermit;
use App\Models\HseReport;
use App\Models\Leave;
use App\Models\MiningActivity;
use App\Models\MiningOtherCost;
use App\Models\OperatorIncentive;
use App\Models\Overtime;
use App\Models\PayrollRun;
use App\Models\PriceList;
use App\Models\PriceVariance;
use App\Models\ProductionBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\QualityHold;
use App\Models\SalesOrder;
use App\Models\StockAdjustment;
use App\Models\StockpileSurvey;
use App\Models\StockTransfer;
use App\Models\SupplierContract;
use App\Models\WeighbridgeTicket;
use App\Models\WorkOrder;

/**
 * Maps an ApprovalRequest to its underlying transaction model so the
 * central ApprovalEngine can update statuses without module-specific logic.
 */
class ApprovalResolver
{
    protected array $map = [
        'PURCHASE_REQUEST' => PurchaseRequest::class,
        'PURCHASE_ORDER' => PurchaseOrder::class,
        'SALES_ORDER' => SalesOrder::class,
        'PRICE_LIST' => PriceList::class,
        'PAYROLL_RUN' => PayrollRun::class,
        'STOCK_ADJUSTMENT' => StockAdjustment::class,
        'STOCK_TRANSFER' => StockTransfer::class,
        'WORK_ORDER' => WorkOrder::class,
        'CSR_PROGRAM' => CsrProgram::class,
        'PRICE_VARIANCE' => PriceVariance::class,
        'LEAVE' => Leave::class,
        'OVERTIME' => Overtime::class,
        'OPERATOR_INCENTIVE' => OperatorIncentive::class,
        'DOCUMENT' => Document::class,
        'LETTER' => LetterRegister::class,
        'MINING_ACTIVITY' => MiningActivity::class,
        'PRODUCTION_BATCH' => ProductionBatch::class,
        'WEIGHBRIDGE_VOID' => WeighbridgeTicket::class,
        'FUEL_ISSUE' => FuelIssue::class,
        'FUEL_RECEIPT' => FuelReceipt::class,
        'STOCKPILE_SURVEY' => StockpileSurvey::class,
        'BUDGET' => Budget::class,
        'CUSTOMER_CONTRACT' => CustomerContract::class,
        'SUPPLIER_CONTRACT' => SupplierContract::class,
        'HAULING_CONTRACT' => HaulingContract::class,
        'MINING_COST' => MiningOtherCost::class,
        'HSE_PERMIT' => HsePermit::class,
        'HSE_CLOSE' => HseReport::class,
        'QUALITY_SPECIAL' => QualityHold::class,
    ];

    public function resolve(ApprovalRequest $request)
    {
        $class = $this->map[$request->transaction_type] ?? null;
        if (! $class) {
            return null;
        }

        return $class::find($request->transaction_id);
    }

    public function applyStatus(ApprovalRequest $request, string $finalStatus): void
    {
        $txn = $this->resolve($request);
        if (! $txn) {
            return;
        }
        // side-effect handlers per tipe transaksi baru
        if ($finalStatus === 'APPROVED') {
            match ($request->transaction_type) {
                'PURCHASE_REQUEST' => $this->approvePurchaseRequest($request, $txn),
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

    protected function approvePurchaseRequest(ApprovalRequest $request, $pr): void
    {
        $this->approveDefault($request, $pr);
        // same budget commitment as direct approve (idempotent via updateOrCreate);
        // DomainException bubbles up so block-mode budget control rejects the approval
        BudgetService::commitPurchaseRequest($pr->fresh());
    }

    protected function approveSurvey(ApprovalRequest $request, $survey): void
    {
        $notes = $this->lastApprovalNotes($request) ?? $survey->investigation;
        StockpileService::approveSurvey($survey, $notes);
    }

    protected function approveQualitySpecial(ApprovalRequest $request, $hold): void
    {
        QualityService::specialApprove(
            $hold,
            $this->lastApprovalNotes($request) ?? 'Special approval via approval center',
            $this->lastApproverId($request)
        );
    }

    protected function approveHseClose(ApprovalRequest $request, $report): void
    {
        HseService::closeReport($report);
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
