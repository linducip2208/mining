<?php

namespace App\Services;

use App\Models\HseCorrectiveAction;
use App\Models\HseReport;
use Illuminate\Support\Facades\DB;

/**
 * HSE workflow: report -> corrective actions -> closure with evidence.
 * A report can only CLOSE when all actions are DONE/VERIFIED.
 */
class HseService
{
    public static function severities(): array
    {
        $raw = \App\Models\Setting::get('hse.severity_levels');
        if ($raw) {
            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
            if (is_array($decoded) && $decoded) {
                return $decoded;
            }
        }
        return ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
    }

    public static function report(array $data): HseReport
    {
        return DB::transaction(function () use ($data) {
            $report = HseReport::create([
                'number' => NumberingService::generate('HSE'),
                'company_id' => $data['company_id'],
                'site_id' => $data['site_id'] ?? null,
                'kind' => $data['kind'],
                'location' => $data['location'] ?? null,
                'occurred_at' => $data['occurred_at'],
                'employee_id' => $data['employee_id'] ?? null,
                'equipment_id' => $data['equipment_id'] ?? null,
                'severity' => $data['severity'] ?? 'LOW',
                'description' => $data['description'],
                'cause' => $data['cause'] ?? null,
                'immediate_action' => $data['immediate_action'] ?? null,
                'photo' => $data['photo'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => 'OPEN',
                'reported_by' => auth()->id(),
                'created_by' => auth()->id() ?? 1,
            ]);
            AuditService::created('HSE', $report);

            // high-severity incident auto-notifies safety roles
            if (in_array($report->severity, ['HIGH', 'CRITICAL'])) {
                $users = \App\Models\User::where('status', 'ACTIVE')
                    ->whereHas('roles', fn ($r) => $r->whereIn('roles.code', ['SUPER_ADMIN', 'SYSTEM_ADMIN', 'HSE_MANAGER', 'SITE_MANAGER']))
                    ->get();
                if ($users->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\SystemAlert(
                        'SAFETY_INCIDENT',
                        'Insiden K3 ' . $report->severity,
                        collect([(object) ['id' => $report->id, 'number' => $report->number . ' — ' . $report->location]])
                    ));
                }
            }
            return $report;
        });
    }

    public static function addAction(int $reportId, array $data): HseCorrectiveAction
    {
        $action = HseCorrectiveAction::create([
            'hse_report_id' => $reportId,
            'action' => $data['action'],
            'responsible_id' => $data['responsible_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => 'OPEN',
            'created_by' => auth()->id() ?? 1,
        ]);
        HseReport::whereKey($reportId)->update(['status' => 'IN_PROGRESS']);
        AuditService::created('HSE', $action);
        return $action;
    }

    /**
     * Close action — closure evidence is mandatory.
     */
    public static function closeAction(HseCorrectiveAction $action, string $evidence, ?string $file = null): void
    {
        if (trim($evidence) === '' && !$file) {
            throw new \DomainException('Bukti penyelesaian (evidence) wajib diisi.');
        }
        $action->update([
            'status' => 'DONE',
            'evidence' => $evidence,
            'evidence_file' => $file,
            'closed_at' => now(),
        ]);
        AuditService::log('UPDATE', 'HSE', $action->id, HseCorrectiveAction::class, null, ['status' => 'DONE']);
    }

    public static function closeReport(HseReport $report): void
    {
        $open = $report->correctiveActions()->whereIn('status', ['OPEN'])->count();
        // overdue computed dynamically; also block when any OPEN past due
        if ($open > 0) {
            throw new \DomainException('Masih ada ' . $open . ' corrective action yang belum selesai.');
        }
        $report->update(['status' => 'CLOSED']);
        AuditService::log('UPDATE', 'HSE', $report->id, HseReport::class, null, ['status' => 'CLOSED']);
    }

    public static function dashboard(?int $companyId, ?int $siteId, string $from, string $to): array
    {
        $base = HseReport::whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId));

        $byKind = (clone $base)->selectRaw('kind, COUNT(*) c')->groupBy('kind')->pluck('c', 'kind');
        $openActions = HseCorrectiveAction::where('status', 'OPEN')
            ->when($companyId || $siteId, function ($q) use ($companyId, $siteId) {
                $q->whereHas('report', function ($w) use ($companyId, $siteId) {
                    $w->when($companyId, fn ($x) => $x->where('company_id', $companyId))
                        ->when($siteId, fn ($x) => $x->where('site_id', $siteId));
                });
            });
        $overdue = (clone $openActions)->whereDate('due_date', '<', today())->count();

        // days without LTI-or-worse accident
        $lastSevere = (clone $base)->whereIn('severity', ['LTI', 'FATALITY', 'HIGH', 'CRITICAL'])
            ->orderByDesc('occurred_at')->value('occurred_at');
        $safeDays = $lastSevere ? (int) \Carbon\Carbon::parse($lastSevere)->diffInDays(now()) : null;

        return [
            'incidents' => (int) ($byKind['INCIDENT'] ?? 0),
            'near_miss' => (int) ($byKind['NEAR_MISS'] ?? 0),
            'hazards' => (int) ($byKind['HAZARD'] ?? 0),
            'open_actions' => (clone $openActions)->count(),
            'overdue_actions' => $overdue,
            'safe_days' => $safeDays,
        ];
    }
}
