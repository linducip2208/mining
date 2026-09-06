<?php

namespace App\Models;

class PrintPreference extends BaseModel
{
    protected $table = 'print_preferences';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function printer()
    {
        return $this->belongsTo(PrinterDevice::class, 'last_printer_id');
    }

    public function paperProfile()
    {
        return $this->belongsTo(PaperProfile::class, 'last_paper_profile_id');
    }
}
