<?php

namespace App\Models;

class LetterNumberReservation extends BaseModel
{
    protected $table = 'letter_number_reservations';

    protected $guarded = ['id'];

    public const STATUSES = ['RESERVED', 'USED', 'CANCELLED', 'VOID'];

    public function letter()
    {
        return $this->belongsTo(LetterRegister::class, 'letter_register_id');
    }

    public function reserver()
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }
}
