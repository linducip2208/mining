<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->q, fn ($q) => $q->where('module', 'like', "%{$request->q}%")->orWhere('record_type', 'like', "%{$request->q}%"))
            ->when($request->action, fn ($q) => $q->where('audit_logs.action', $request->action))
            ->when($request->from, fn ($q) => $q->whereDate('audit_logs.created_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('audit_logs.created_at', '<=', $request->to))
            ->latest()->paginate(30)->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
