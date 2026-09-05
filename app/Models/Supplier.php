<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Supplier extends BaseModel
{
    protected $table = 'suppliers';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function company() { return $this->belongsTo(Company::class); }
    public function paymentTerm() { return $this->belongsTo(PaymentTerm::class, 'payment_term_id'); }

}
