<?php

namespace App\Models;

class LetterType extends BaseModel
{
    protected $table = 'letter_types';

    protected $guarded = ['id'];

    public function letters()
    {
        return $this->hasMany(LetterRegister::class, 'letter_type_id');
    }
}
