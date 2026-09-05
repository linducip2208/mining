<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CustomerDeposit extends BaseModel
{
    protected $table = 'customer_deposits';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function customer() { return $this->belongsTo(Customer::class); }

}
