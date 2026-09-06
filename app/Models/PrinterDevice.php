<?php

namespace App\Models;

use Illuminate\Support\Str;

class PrinterDevice extends BaseModel
{
    protected $table = 'printer_devices';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $printer): void {
            $printer->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'document_types' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'auto_print' => 'boolean',
            'supports_auto_cut' => 'boolean',
            'character_width' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function jobs()
    {
        return $this->hasMany(PrintJob::class);
    }

    public function paperProfiles()
    {
        return $this->belongsToMany(PaperProfile::class, 'printer_paper_profile')->withPivot('is_default');
    }

    public function documentPrintProfiles()
    {
        return $this->hasMany(DocumentPrintProfile::class);
    }

    public function supports(string $documentType): bool
    {
        return in_array(strtoupper($documentType), array_map('strtoupper', $this->document_types ?? []), true);
    }

    public function characterWidth(?PaperProfile $paper = null): int
    {
        if ($this->character_width) {
            return (int) $this->character_width;
        }

        $code = strtoupper((string) ($paper?->code ?? $this->paper_size ?? ''));
        return str_contains($code, '58') ? 32 : 42;
    }

    public function supportsPaper(PaperProfile $paper): bool
    {
        if ($this->paperProfiles()->whereKey($paper->getKey())->exists()) {
            return true;
        }

        if ($this->paperProfiles()->exists()) {
            return false;
        }

        $type = strtoupper((string) $this->printer_type);
        if (in_array($type, ['THERMAL_58', 'THERMAL_80', 'WEIGHBRIDGE'], true) || $this->connection_type === 'ESC_POS') {
            return $paper->isThermal() && ($type !== 'THERMAL_58' || (float) $paper->width_mm <= 58);
        }

        return ! $paper->isThermal();
    }
}