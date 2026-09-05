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
        $statusMap = [
            'APPROVED' => 'APPROVED',
            'REJECTED' => 'REJECTED',
            'RETURNED' => 'DRAFT',
            'CANCELLED' => 'CANCELLED',
        ];
        $new = $statusMap[$finalStatus] ?? $finalStatus;
        if (array_key_exists('status', $txn->getAttributes()) || $txn->isFillable('status')) {
            $txn->status = $new;
            if (in_array('approved_by', $txn->getFillable()) && $new === 'APPROVED') {
                $txn->approved_by = auth()->id();
            }
            $txn->save();
            AuditService::log('APPROVE', $request->module, $txn->id, $txn::class, null, ['status' => $new]);
        }
    }
}
