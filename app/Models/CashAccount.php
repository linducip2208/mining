<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CashAccount extends BaseModel
{
    protected $table = 'cash_accounts';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function coa() { return $this->belongsTo(ChartOfAccount::class, 'coa_id'); }
    public function company() { return $this->belongsTo(Company::class); }

}
