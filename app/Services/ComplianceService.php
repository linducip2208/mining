<?php

namespace App\Services;

use App\Models\ComplianceRegister;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Compliance register with H-90/60/30/14/7 + expired reminders.
 * Called daily by `alert:scan` (see ScanAlerts command).
 */
class ComplianceService
{
    public static function defaultReminderDays(): array
    {
        return [90, 60, 30, 14, 7];
    }

    public static function register(array $data): ComplianceRegister
    {
        $reg = ComplianceRegister::create([
            'number' => NumberingService::generate('CMP'),
            'type' => $data['type'],
            'title' => $data['title'],
            'document_number' => $data['document_number'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'site_id' => $data['site_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'equipment_id' => $data['equipment_id'] ?? null,
            'document_id' => $data['document_id'] ?? null,
            'issued_date' => $data['issued_date'] ?? null,
            'effective_date' => $data['effective_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'status' => 'ACTIVE',
            'attachment' => $data['attachment'] ?? null,
            'reminder_days' => $data['reminder_days'] ?? self::defaultReminderDays(),
            'responsible_id' => $data['responsible_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id() ?? 1,
        ]);
        AuditService::created('COMPLIANCE', $reg);
        return $reg;
    }

    /**
     * Renew/extend: perpanjang masa berlaku, status kembali ACTIVE.
     */
    public static function renew(ComplianceRegister $reg, string $expiryDate, ?string $documentNumber = null): ComplianceRegister
    {
        if (!in_array($reg->status, ['ACTIVE', 'EXPIRING_SOON', 'EXPIRED'])) {
            throw new \DomainException('Status ' . $reg->status . ' tidak dapat diperpanjang.');
        }
        $reg->update([
            'expiry_date' => $expiryDate,
            'document_number' => $documentNumber ?? $reg->document_number,
            'status' => 'ACTIVE',
        ]);
        AuditService::log('UPDATE', 'COMPLIANCE', $reg->id, ComplianceRegister::class, null, ['renewed_until' => $expiryDate]);
        return $reg->fresh();
    }

    /**
     * Evaluate all active registers; update EXPIRING_SOON/EXPIRED and
     * notify once per threshold crossing.
     */
    public static function dispatchReminders(): array
    {
        $today = now()->startOfDay();
        $sent = [];
        $regs = ComplianceRegister::whereIn('status', ['ACTIVE', 'EXPIRING_SOON'])
            ->whereNotNull('expiry_date')
            ->get();

        foreach ($regs as $reg) {
            $days = (int) $today->diffInDays($reg->expiry_date, false);
            if ($days < 0) {
                if ($reg->status !== 'EXPIRED') {
                    $reg->update(['status' => 'EXPIRED']);
                    self::notify([$reg], 'Izin kedaluwarsa (EXPIRED)');
                    $sent[] = $reg->number;
                }
                continue;
            }
            $thresholds = $reg->reminder_days ?: self::defaultReminderDays();
            sort($thresholds);
            foreach ($thresholds as $h) {
                if ($days <= $h) {
                    $last = $reg->last_reminded_at ? (int) $today->diffInDays($reg->last_reminded_at, false) : null;
                    // notify once per day max per register
                    if ($last === 0 && $reg->status === 'EXPIRING_SOON') {
                        continue 2;
                    }
                    $reg->update(['status' => 'EXPIRING_SOON', 'last_reminded_at' => $today->toDateString()]);
                    self::notify([$reg], "H-{$days} kedaluwarsa");
                    $sent[] = $reg->number;
                    continue 2;
                }
            }
        }
        return $sent;
    }

    protected static function notify(array $regs, string $suffix): void
    {
        $users = \App\Models\User::where('status', 'ACTIVE')
            ->whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN', 'SYSTEM_ADMIN', 'COMPLIANCE_OFFICER', 'HSE_MANAGER', 'SITE_MANAGER']))
            ->get();
        if ($users->isEmpty()) {
            return;
        }
        Notification::send($users, new SystemAlert(
            'COMPLIANCE_EXPIRY',
            'Compliance: ' . $suffix,
            collect($regs)->map(fn ($r) => (object) ['id' => $r->id, 'number' => $r->number . ' — ' . $r->title])
        ));
    }

    public static function calendar(string $from, string $to, ?int $companyId = null, ?int $siteId = null)
    {
        return ComplianceRegister::with(['employee', 'equipment'])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$from, $to])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->whereNotIn('status', ['CANCELLED'])
            ->orderBy('expiry_date')
            ->get();
    }
}
