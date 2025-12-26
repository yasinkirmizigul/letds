<?php

namespace App\Http\Controllers\Admin\AuditLog;

use App\Http\Controllers\Controller;
use App\Models\Admin\AuditLog\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $action = (string) $request->get('action', '');
        $status = (string) $request->get('status', '');
        $method = (string) $request->get('method', '');
        $perpage = (int) $request->get('perpage', 25);

        $rows = AuditLog::query()
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('route', 'like', "%{$q}%")
                        ->orWhere('uri', 'like', "%{$q}%")
                        ->orWhere('user_email', 'like', "%{$q}%")
                        ->orWhere('user_name', 'like', "%{$q}%")
                        ->orWhere('ip', 'like', "%{$q}%");
                });
            })
            ->when($action, fn($qq) => $qq->where('action', $action))
            ->when($status, fn($qq) => $qq->where('status', (int) $status))
            ->when($method, fn($qq) => $qq->where('method', strtoupper($method)))
            ->orderByDesc('id')
            ->paginate(max(10, min(200, $perpage)))
            ->withQueryString();

        return view('admin.pages.audit-logs.index', [
            'rows' => $rows,
            'filters' => compact('q','action','status','method','perpage'),
            'pageTitle' => 'Loglar',
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        return view('admin.pages.audit-logs.show', [
            'row' => $auditLog,
        ]);
    }
}
