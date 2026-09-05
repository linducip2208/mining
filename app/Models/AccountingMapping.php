<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AccountingMapping extends BaseModel
{
    protected $table = 'accounting_mappings';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function chartOfAccount() { return $this->belongsTo(ChartOfAccount::class); }

}
