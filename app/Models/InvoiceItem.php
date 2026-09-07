<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class InvoiceItem extends BaseModel
{
    protected $table = 'invoice_items';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function item() { return $this->belongsTo(Item::class); }

}
