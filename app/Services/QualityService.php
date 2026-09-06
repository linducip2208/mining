<?php

namespace App\Services;

use App\Models\CoaDocument;
use App\Models\DeliveryOrder;
use App\Models\ProductSpecification;
use App\Models\QcSample;
use App\Models\QcTest;
use App\Models\QualityHold;
use Illuminate\Support\Facades\DB;

/**
 * QC: sample -> test vs applicable spec (customer > site > general).
 * FAIL auto-creates HOLD which blocks DO completion until released
 * or covered by special approval.
 */
class QualityService
{
    /**
     * Find applicable spec: customer-specific > site > general, active & effective.
     */
    public static function applicableSpec(int $itemId, ?int $customerId = null, ?int $siteId = null, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        $base = ProductSpecification::with('lines.parameter')
            ->where('item_id', $itemId)
            ->where('status', 'ACTIVE')
            ->whereDate('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', $date);
            });
        return (clone $base)->where('customer_id', $customerId)->first()
            ?? (clone $base)->whereNull('customer_id')->where('site_id', $siteId)->first()
            ?? (clone $base)->whereNull('customer_id')->whereNull('site_id')->first();
    }

    public static function createSample(array $data): QcSample
    {
        $sample = QcSample::create([
            'number' => NumberingService::generate('QC'),
            'company_id' => $data['company_id'] ?? null,
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'item_id' => $data['item_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'sample_date' => $data['sample_date'] ?? now()->toDateString(),
            'status' => 'PENDING',
            'created_by' => auth()->id() ?? 1,
        ]);
        AuditService::created('QUALITY', $sample);
        return $sample;
    }

    /**
     * Record a test result; auto PASS/FAIL vs spec; FAIL creates HOLD.
     */
    public static function recordTest(int $sampleId, int $parameterId, float $value, ?string $notes = null): QcTest
    {
        return DB::transaction(function () use ($sampleId, $parameterId, $value, $notes) {
            $sample = QcSample::with('tests')->findOrFail($sampleId);
            $spec = $sample->item_id
                ? self::applicableSpec($sample->item_id, $sample->customer_id, null, $sample->sample_date->toDateString())
                : null;
            $line = $spec?->lines->firstWhere('quality_parameter_id', $parameterId);

            $result = 'PASS';
            if ($line) {
                if ($line->min_value !== null && $value < (float) $line->min_value) {
                    $result = 'FAIL';
                }
                if ($line->max_value !== null && $value > (float) $line->max_value) {
                    $result = 'FAIL';
                }
            }

            $test = QcTest::create([
                'qc_sample_id' => $sample->id,
                'quality_parameter_id' => $parameterId,
                'result_value' => $value,
                'result' => $result,
                'notes' => $notes,
            ]);

            if ($result === 'FAIL') {
                $sample->update(['status' => 'HOLD']);
                QualityHold::create([
                    'number' => NumberingService::generate('QH'),
                    'item_id' => $sample->item_id,
                    'customer_id' => $sample->customer_id,
                    'qc_sample_id' => $sample->id,
                    'reason' => 'Uji gagal: parameter #' . $parameterId . ' = ' . $value,
                    'status' => 'HOLD',
                    'created_by' => auth()->id() ?? 1,
                ]);
            } else {
                // all tests pass so far -> mark sample PASS
                if ($sample->tests()->where('result', 'FAIL')->doesntExist()) {
                    $sample->update(['status' => 'PASS', 'tested_by' => auth()->id(), 'tested_at' => now()]);
                }
            }

            AuditService::log('UPDATE', 'QUALITY', $sample->id, QcSample::class, null, ['test' => $parameterId, 'result' => $result]);
            return $test;
        });
    }

    /**
     * Active holds blocking a delivery order (direct, via SO, or via item+customer).
     */
    public static function blockingHolds(DeliveryOrder $do)
    {
        $so = $do->salesOrder;
        $itemIds = $do->items->pluck('item_id')->all();
        return QualityHold::where('status', 'HOLD')
            ->whereNull('special_approval_by')
            ->where(function ($q) use ($do, $so, $itemIds) {
                $q->where('delivery_order_id', $do->id)
                    ->orWhere(function ($qq) use ($so) {
                        if ($so) {
                            $qq->where('sales_order_id', $so->id);
                        }
                    })
                    ->orWhere(function ($qq) use ($itemIds, $so) {
                        $qq->whereIn('item_id', $itemIds)
                            ->when($so, fn ($w) => $w->where('customer_id', $so->customer_id));
                    });
            })->get();
    }

    public static function assertDeliveryClear(DeliveryOrder $do): void
    {
        $holds = self::blockingHolds($do);
        if ($holds->isNotEmpty()) {
            throw new \DomainException('Delivery ditahan QC (' . $holds->pluck('number')->implode(', ') . '). Release hold atau minta special approval.');
        }
    }

    public static function hold(array $data): QualityHold
    {
        $hold = QualityHold::create([
            'number' => NumberingService::generate('QH'),
            'sales_order_id' => $data['sales_order_id'] ?? null,
            'delivery_order_id' => $data['delivery_order_id'] ?? null,
            'item_id' => $data['item_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'reason' => $data['reason'] ?? 'Quality hold manual',
            'status' => 'HOLD',
            'created_by' => auth()->id() ?? 1,
        ]);
        AuditService::created('QUALITY', $hold);
        return $hold;
    }

    public static function release(QualityHold $hold, ?string $note = null): void
    {
        if ($hold->status !== 'HOLD') {
            throw new \DomainException('Hold tidak dalam status HOLD.');
        }
        $hold->update([
            'status' => 'RELEASED',
            'released_by' => auth()->id(),
            'released_at' => now(),
        ]);
        AuditService::log('UPDATE', 'QUALITY', $hold->id, QualityHold::class, null, ['status' => 'RELEASED'], $note);
    }

    public static function specialApprove(QualityHold $hold, ?string $note = null): void
    {
        if ($hold->status !== 'HOLD') {
            throw new \DomainException('Hold tidak dalam status HOLD.');
        }
        $hold->update([
            'special_approval_by' => auth()->id(),
            'special_approval_note' => $note,
        ]);
        AuditService::log('APPROVE', 'QUALITY', $hold->id, QualityHold::class, null, ['special_approval' => true], $note);
    }

    public static function issueCoa(int $sampleId, ?int $customerId = null, ?int $invoiceId = null): CoaDocument
    {
        $sample = QcSample::with('tests.parameter')->findOrFail($sampleId);
        if ($sample->status === 'REJECT') {
            throw new \DomainException('Sampel REJECT tidak dapat diterbitkan CoA.');
        }
        $results = $sample->tests->map(fn ($t) => [
            'parameter' => $t->parameter?->code,
            'unit' => $t->parameter?->unit,
            'value' => (float) $t->result_value,
            'result' => $t->result,
        ])->all();
        $coa = CoaDocument::create([
            'number' => NumberingService::generate('COA'),
            'qc_sample_id' => $sample->id,
            'customer_id' => $customerId ?? $sample->customer_id,
            'invoice_id' => $invoiceId,
            'issue_date' => now()->toDateString(),
            'results' => $results,
            'status' => 'ISSUED',
            'created_by' => auth()->id() ?? 1,
        ]);
        AuditService::created('QUALITY', $coa);
        return $coa;
    }
}
