<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Menu extends BaseModel
{
    protected $table = 'menus';
    protected $guarded = ['id'];

    public function children() { return $this->hasMany(self::class); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

}
