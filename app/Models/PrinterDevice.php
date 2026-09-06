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

    public function supports(string $documentType): bool
    {
        return in_array(strtoupper($documentType), array_map('strtoupper', $this->document_types ?? []), true);
    }
}
