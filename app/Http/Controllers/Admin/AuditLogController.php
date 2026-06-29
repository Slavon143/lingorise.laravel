<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AdminAuditLog::query()
            ->with(['admin:id,name,email'])
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->query('action')))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $actions = AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actions' => $actions,
        ]);
    }
}
