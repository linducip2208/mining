<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

abstract class BaseModel extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // auto date casts from DB schema, driver-agnostic (cached per table)
        $casts = Cache::remember('schema_dates.' . $this->getTable(), 3600, function () {
            $out = [];
            try {
                foreach (Schema::getColumns($this->getTable()) as $col) {
                    $type = strtolower($col['type_name'] ?? '');
                    if ($type === 'date') {
                        $out[$col['name']] = 'date';
                    } elseif (in_array($type, ['datetime', 'datetimetz', 'timestamp', 'timestamptz'], true)) {
                        $out[$col['name']] = 'datetime';
                    }
                }
            } catch (\Throwable $e) {
                // table missing (tests) — fall back to naming convention below
            }
            // naming-convention fallback (works even without schema access)
            foreach (['date', 'trx_date', 'journal_date', 'invoice_date', 'due_date', 'order_date', 'delivery_date', 'bill_date', 'payment_date', 'receipt_date', 'request_date', 'required_date', 'expected_date', 'transfer_date', 'adjustment_date', 'deposit_date', 'effective_date', 'expiry_date', 'join_date', 'end_date', 'birth_date', 'acquisition_date', 'start_date', 'calibration_date', 'valid_until', 'reminder_date', 'last_done', 'next_due', 'planned_finish'] as $name) {
                $out[$name] ??= 'date';
            }
            return $out;
        });

        $this->mergeCasts($casts);
    }
}
