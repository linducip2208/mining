<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\ApprovalDelegation;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Central Approval Engine.
 * All modules submit through here; no module-specific approval logic.
 */
class ApprovalService
{
    /**
     * Submit a transaction for approval.
     * Sets transaction status to SUBMITTED and builds the request + pending actions.
     */
    public static function submit(string $module, string $transactionType, $transaction): ?ApprovalRequest
    {
        return DB::transaction(function () use ($module, $transactionType, $transaction) {
            $amount = self::amountOf($transaction);

            $workflow = ApprovalWorkflow::where('module', $module)
                ->where('transaction_type', $transactionType)
                ->where('is_active', true)
                ->where(function ($q) use ($transaction) {
                    if (isset($transaction->company_id)) {
                        $q->whereNull('company_id')->orWhere('company_id', $transaction->company_id);
                    }
                    if (isset($transaction->site_id)) {
                        $q->whereNull('site_id')->orWhere('site_id', $transaction->site_id);
                    }
                })
                ->orderByRaw('company_id IS NULL, site_id IS NULL')
                ->first();

            if (!$workflow) {
                // No workflow configured: auto-approve and mark transaction APPROVED
                $transaction->status = 'APPROVED';
                if ($transaction->isFillable('approved_by')) {
                    $transaction->approved_by = auth()->id();
                }
                $transaction->save();
                return null;
            }

            $steps = $workflow->steps->sortBy('sequence')->filter(
                fn (ApprovalStep $s) => $amount >= (float) $s->min_amount
                    && ($s->max_amount === null || $amount <= (float) $s->max_amount)
            );

            if ($steps->isEmpty()) {
                $transaction->status = 'APPROVED';
                if ($transaction->isFillable('approved_by')) {
                    $transaction->approved_by = auth()->id();
                }
                $transaction->save();
                return null;
            }

            if ($transaction->isFillable('status')) {
                $transaction->status = 'SUBMITTED';
                $transaction->save();
            }

            $request = ApprovalRequest::create([
                'number' => NumberingService::generate('APR'),
                'approval_workflow_id' => $workflow->id,
                'module' => $module,
                'transaction_type' => $transactionType,
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->number ?? null,
                'amount' => $amount,
                'status' => 'PENDING',
                'requested_by' => auth()->id() ?? 1,
                'submitted_at' => now(),
            ]);

            $seq = 1;
            foreach ($steps as $step) {
                $approverId = self::resolveApprover($step, $transaction);
                if ($approverId) {
                    ApprovalAction::create([
                        'approval_request_id' => $request->id,
                        'sequence' => $seq++,
                        'step_id' => $step->id,
                        'approver_id' => $approverId,
                        'action' => 'PENDING',
                        'acted_at' => now(),
                    ]);
                }
            }

            $first = $request->actions()->where('action', 'PENDING')->orderBy('sequence')->first();
            if ($first) {
                self::notify($first->approver_id, $request);
            } else {
                // tidak ada approver ter-resolve (role tanpa holder) → auto-approve
                // agar request tidak stuck selamanya; tercatat di audit
                $request->status = 'APPROVED';
                $request->finished_at = now();
                $request->save();
                if ($transaction->isFillable('status')) {
                    $transaction->status = 'APPROVED';
                    $transaction->save();
                }
                AuditService::log('APPROVE', $module, $transaction->id, $transaction::class, null, ['status' => 'APPROVED', 'auto' => 'no-approver']);
            }

            AuditService::log('SUBMIT', $module, $transaction->id, $transaction::class, null, ['status' => 'SUBMITTED', 'approval_request' => $request->number]);

            return $request;
        });
    }

    /**
     * Approver acts on the earliest pending action assigned to them.
     */
    public static function act(ApprovalRequest $request, User $user, string $action, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($request, $user, $action, $notes) {
            $pending = $request->actions()
                ->where('action', 'PENDING')
                ->orderBy('sequence')
                ->get()
                ->first(fn ($a) => self::canApprove($a, $user));

            if (!$pending) {
                return false;
            }

            $pending->action = $action;
            $pending->notes = $notes;
            $pending->acted_at = now();
            $pending->approver_id = $user->id;
            $pending->save();

            AuditService::log($action === 'APPROVE' ? 'APPROVE' : 'REJECT', $request->module, $request->transaction_id, null, null, ['approval_request' => $request->number, 'action' => $action, 'notes' => $notes], $notes);

            if ($action === 'APPROVE') {
                $stillPending = $request->actions()->where('action', 'PENDING')->orderBy('sequence')->first();
                if ($stillPending) {
                    self::notify($stillPending->approver_id, $request);
                } else {
                    $request->status = 'APPROVED';
                    $request->finished_at = now();
                    $request->save();
                    app(ApprovalResolver::class)->applyStatus($request, 'APPROVED');
                }
            } elseif ($action === 'REJECT') {
                $request->status = 'REJECTED';
                $request->finished_at = now();
                $request->save();
                app(ApprovalResolver::class)->applyStatus($request, 'REJECTED');
            } elseif ($action === 'RETURN') {
                $request->status = 'RETURNED';
                $request->finished_at = now();
                $request->save();
                app(ApprovalResolver::class)->applyStatus($request, 'RETURNED');
            }

            return true;
        });
    }

    /**
     * Approve/Reject/Return directly by approval action id (from approval center UI).
     */
    public static function actOnActionId(int $approvalActionId, User $user, string $action, ?string $notes = null): bool
    {
        $pendingAction = \App\Models\ApprovalAction::with('request')->find($approvalActionId);
        if (!$pendingAction || $pendingAction->action !== 'PENDING' || $pendingAction->request->status !== 'PENDING') {
            return false;
        }
        if (!self::canApprove($pendingAction, $user)) {
            return false;
        }

        $request = $pendingAction->request;
        $pendingAction->action = strtoupper($action);
        $pendingAction->notes = $notes;
        $pendingAction->acted_at = now();
        $pendingAction->save();

        AuditService::log($action === 'APPROVE' ? 'APPROVE' : 'REJECT', $request->module, $request->transaction_id, null, null, ['approval_request' => $request->number, 'action' => $action], $notes);

        if ($action === 'APPROVE') {
            $next = $request->actions()->where('action', 'PENDING')->orderBy('sequence')->first();
            if ($next) {
                self::notify($next->approver_id, $request);
                return true;
            }
            $request->status = 'APPROVED';
            $request->finished_at = now();
            $request->save();
            app(ApprovalResolver::class)->applyStatus($request, 'APPROVED');
        } elseif ($action === 'REJECT') {
            $request->status = 'REJECTED';
            $request->finished_at = now();
            $request->save();
            app(ApprovalResolver::class)->applyStatus($request, 'REJECTED');
        } elseif ($action === 'RETURN') {
            $request->status = 'RETURNED';
            $request->finished_at = now();
            $request->save();
            app(ApprovalResolver::class)->applyStatus($request, 'RETURNED');
        }

        return true;
    }

    public static function pendingFor(User $user)
    {
        $delegatedIds = ApprovalDelegation::where('delegate_id', $user->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->pluck('user_id');

        $approverIds = [$user->id];
        if ($delegatedIds->isNotEmpty()) {
            $approverIds = array_merge($approverIds, $delegatedIds->all());
        }

        return ApprovalAction::query()
            ->where('action', 'PENDING')
            ->whereIn('approver_id', $approverIds)
            ->whereHas('request', fn ($q) => $q->where('status', 'PENDING'))
            ->with('request')
            ->get();
    }

    protected static function canApprove($action, User $user): bool
    {
        if ($action->approver_id === $user->id) {
            return true;
        }
        // delegation
        return ApprovalDelegation::where('user_id', $action->approver_id)
            ->where('delegate_id', $user->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();
    }

    protected static function resolveApprover(ApprovalStep $step, $transaction): ?int
    {
        if ($step->user_id) {
            return $step->user_id;
        }
        if (!$step->role_id) {
            return null;
        }
        $siteId = $transaction->site_id ?? null;
        $companyId = $transaction->company_id ?? null;

        // Progressive scope loosening: exact site → same company → any active holder.
        // Guarantees a request is never stuck without an approver when the role exists.
        $levels = [
            ['company' => $companyId, 'site' => $siteId],
            ['company' => $companyId, 'site' => null],
            ['company' => null, 'site' => null],
        ];

        foreach ($levels as $level) {
            $user = User::whereHas('roles', function ($q) use ($step, $level) {
                $q->where('roles.id', $step->role_id);
                if ($level['company'] !== null) {
                    $q->where(function ($qq) use ($level) {
                        $qq->whereNull('role_user.company_id')->orWhere('role_user.company_id', $level['company']);
                    });
                }
                if ($level['site'] !== null) {
                    $q->where(function ($qq) use ($level) {
                        $qq->whereNull('role_user.site_id')->orWhere('role_user.site_id', $level['site']);
                    });
                }
            })
            ->where('status', 'ACTIVE')
            ->first();

            if ($user) {
                return $user->id;
            }
        }

        return null;
    }

    protected static function amountOf($transaction): float
    {
        foreach (['total', 'amount', 'total_amount', 'budget'] as $field) {
            if (isset($transaction->{$field})) {
                return (float) $transaction->{$field};
            }
        }
        return 0.0;
    }

    protected static function notify(int $userId, ApprovalRequest $request): void
    {
        $user = User::find($userId);
        if ($user && method_exists($user, 'notify')) {
            $user->notify(new \App\Notifications\ApprovalPending($request));
        }
    }
}
