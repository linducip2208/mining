<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Setting extends BaseModel
{
    protected $table = 'settings';
    protected $guarded = ['id'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
public static function get($key, $default=null){ $s = static::where("key",$key)->first(); return $s ? $s->value : $default; } public static function set($key,$value,$type="string"){ static::updateOrCreate(["key"=>$key],["value"=>$value,"type"=>$type,"updated_by"=>auth()->id()]); }
}
