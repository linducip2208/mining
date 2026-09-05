<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class TaxTransaction extends BaseModel
{
    protected $table = 'tax_transactions';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    public function taxCode() { return $this->belongsTo(TaxCode::class, 'tax_code_id'); }
    public function company() { return $this->belongsTo(Company::class); }
}
