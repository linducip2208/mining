<?php

namespace App\Models;

class DocumentPrintProfile extends BaseModel
{
    protected $table = 'document_print_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'copies' => 'integer',
            'auto_print' => 'boolean',
            'auto_cut' => 'boolean',
            'print_logo' => 'boolean',
            'print_qr' => 'boolean',
            'print_signature' => 'boolean',
            'print_watermark' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function paperProfile()
    {
        return $this->belongsTo(PaperProfile::class);
    }

    public function printer()
    {
        return $this->belongsTo(PrinterDevice::class, 'printer_device_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
