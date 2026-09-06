<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaperProfile extends BaseModel
{
    protected $table = 'paper_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'width_mm' => 'float',
            'height_mm' => 'float',
            'margin_top_mm' => 'float',
            'margin_right_mm' => 'float',
            'margin_bottom_mm' => 'float',
            'margin_left_mm' => 'float',
            'is_continuous' => 'boolean',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function printers(): BelongsToMany
    {
        return $this->belongsToMany(PrinterDevice::class, 'printer_paper_profile')->withPivot('is_default');
    }

    public function documentPrintProfiles()
    {
        return $this->hasMany(DocumentPrintProfile::class);
    }

    public function isThermal(): bool
    {
        return $this->paper_type === 'THERMAL';
    }

    public function isFixedHeight(): bool
    {
        return ! $this->is_continuous && $this->height_mm !== null;
    }
}
