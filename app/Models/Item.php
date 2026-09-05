<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Item extends BaseModel
{
    protected $table = 'items';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function category() { return $this->belongsTo(ItemCategory::class, 'item_category_id'); }
    public function unit() { return $this->belongsTo(Unit::class); }

}
