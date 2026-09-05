<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Payment extends BaseModel
{
    protected $table = 'payments';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function cashAccount() { return $this->belongsTo(CashAccount::class, 'cash_account_id'); }
    public function allocations() { return $this->hasMany(PaymentAllocation::class); }

}
