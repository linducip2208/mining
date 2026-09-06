<?php

namespace App\Models;

use Illuminate\Support\Str;

class PrintJob extends BaseModel
{
    protected $table = 'print_jobs';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $job): void {
            $job->uuid ??= (string) Str::uuid();
            $job->requested_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'requested_at' => 'datetime',
            'printed_at' => 'datetime',
        ];
    }

    public function printer()
    {
        return $this->belongsTo(PrinterDevice::class, 'printer_device_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['PRINTED', 'CANCELLED'], true);
    }
}
