<?php

namespace App\Models;

class JournalEntry extends BaseModel
{
    protected $table = 'journal_entries';

    protected $guarded = ['id'];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals()
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    /**
     * Deep-link ke dokumen sumber (konektivitas UI antar modul).
     * Return [route_name, route_param] atau null bila tidak ada tautan.
     */
    public function sourceLink(): ?array
    {
        return match ($this->source_type) {
            'SALES_INVOICE', 'DEPOSIT_ALLOCATION' => ['invoices.show', $this->source_id],
            'VENDOR_BILL' => ['vendor-bills.show', $this->source_id],
            'PAYMENT' => ($this->source_number && str_starts_with((string) $this->source_number, 'RCV'))
                ? ['payments.show', $this->source_id]
                : null,
            'PRODUCTION' => ['production-batches.show', $this->source_id],
            'MAINTENANCE_PART' => ['work-orders.show', $this->source_id],
            'PAYROLL', 'PAYROLL_PAYMENT' => ['payroll-runs.show', $this->source_id],
            'CSR' => ['csr.show', $this->source_id],
            'CUSTOMER_DEPOSIT' => ($cid = CustomerDeposit::whereKey($this->source_id)->value('customer_id'))
                ? ['deposit.statement', $cid]
                : null,
            default => null,
        };
    }
}
