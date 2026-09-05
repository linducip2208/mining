<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ApprovalCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $mine = ApprovalService::pendingFor($user);

        if ($user->isSuperAdmin() || $user->hasPermission('approval.view')) {
            $all = ApprovalRequest::with('requestedBy')->latest('submitted_at')
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->module, fn ($q) => $q->where('module', $request->module))
                ->paginate(20)->withQueryString();
        } else {
            $all = ApprovalRequest::whereRaw('1=0')->paginate(20);
        }

        return view('approvals.index', [
            'pending' => $mine,
            'requests' => $all,
        ]);
    }

    public function act(Request $request, string $action)
    {
        $validated = $request->validate([
            'approval_action_id' => 'required|exists:approval_actions,id',
            'notes' => 'nullable|max:1000',
        ]);

        $result = ApprovalService::actOnActionId(
            (int) $validated['approval_action_id'],
            auth()->user(),
            strtoupper($action),
            $validated['notes'] ?? null
        );

        if (!$result) {
            return back()->with('error', 'Tindakan tidak valid atau bukan giliran Anda.');
        }

        $message = $action === 'approve' ? 'Transaksi disetujui.' : ($action === 'reject' ? 'Transaksi ditolak.' : 'Transaksi dikembalikan.');
        return back()->with('success', $message);
    }
}
