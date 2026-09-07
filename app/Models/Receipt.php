<?php

namespace App\Models;

class Receipt extends BaseModel
{
    protected $table = 'receipts';

    protected $guarded = ['id'];

    public const STATUSES = ['DRAFT', 'ISSUED', 'CONFIRMED', 'PAID', 'VOID'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cashAccount()
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
