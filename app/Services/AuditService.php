<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    protected static bool $enabled = true;

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function log(
        string $action,
        string $module,
        $recordId = null,
        $recordType = null,
        $oldValues = null,
        $newValues = null,
        ?string $reason = null
    ): void {
        if (!self::$enabled) {
            return;
        }
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'record_type' => $recordType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
        ]);
    }

    public static function created(string $module, $record): void
    {
        self::log('CREATE', $module, $record->id ?? null, $record::class, null, $record->toArray());
    }

    public static function updated(string $module, $record, array $old = null): void
    {
        self::log('UPDATE', $module, $record->id ?? null, $record::class, $old ?? $record->getOriginal(), $record->toArray());
    }

    public static function deleted(string $module, $record): void
    {
        self::log('DELETE', $module, $record->id ?? null, $record::class, $record->toArray(), null);
    }
}
