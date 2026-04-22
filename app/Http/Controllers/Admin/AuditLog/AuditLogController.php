<?php

namespace App\Http\Controllers\Admin\AuditLog;

use App\Http\Controllers\Controller;
use App\Models\Admin\AuditLog\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->string('mode', 'all')->toString();
        $q = trim((string) $request->get('q', ''));
        $action = trim((string) $request->get('action', ''));
        $status = trim((string) $request->get('status', ''));
        $method = strtoupper(trim((string) $request->get('method', '')));
        $perpage = max(10, min(200, (int) $request->get('perpage', 25)));

        $query = AuditLog::query()->latest('id');

        $this->applyModeFilter($query, $mode);
        $this->applyFilters($query, [
            'q' => $q,
            'action' => $action,
            'status' => $status,
            'method' => $method,
        ]);

        $rows = $query
            ->paginate($perpage)
            ->withQueryString();

        $stats = [
            'total' => AuditLog::count(),
            'system' => $this->modeCount('system'),
            'user' => $this->modeCount('user'),
            'errors' => AuditLog::query()->where('status', '>=', 400)->count(),
            'slow' => AuditLog::query()->where('duration_ms', '>=', 1000)->count(),
        ];

        $actionOptions = AuditLog::query()
            ->whereNotNull('action')
            ->where('action', '!=', '')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.pages.audit-logs.index', [
            'rows' => $rows,
            'mode' => $mode,
            'stats' => $stats,
            'actionOptions' => $actionOptions,
            'filters' => compact('q', 'action', 'status', 'method', 'perpage'),
            'pageTitle' => 'Loglar',
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        return view('admin.pages.audit-logs.show', [
            'row' => $auditLog,
        ]);
    }

    private function modeCount(string $mode): int
    {
        $query = AuditLog::query();
        $this->applyModeFilter($query, $mode);

        return $query->count();
    }

    private function applyModeFilter(Builder $query, string $mode): void
    {
        if ($mode === 'system') {
            $query->where(function (Builder $builder) {
                $builder->where('is_system', 1)
                    ->orWhere('method', 'CLI')
                    ->orWhere('user_agent', 'CLI');
            });
            return;
        }

        if ($mode === 'user') {
            $query->where(function (Builder $builder) {
                $builder->whereNull('is_system')
                    ->orWhere('is_system', 0);
            })->where(function (Builder $builder) {
                $builder->whereNull('method')
                    ->orWhere('method', '!=', 'CLI');
            });
        }
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, function (Builder $builder, string $term) {
                $builder->where(function (Builder $nested) use ($term) {
                    $nested->where('route', 'like', "%{$term}%")
                        ->orWhere('uri', 'like', "%{$term}%")
                        ->orWhere('user_email', 'like', "%{$term}%")
                        ->orWhere('user_name', 'like', "%{$term}%")
                        ->orWhere('ip', 'like', "%{$term}%");
                });
            })
            ->when($filters['action'] ?? null, fn (Builder $builder, string $action) => $builder->where('action', $action))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', (int) $status))
            ->when($filters['method'] ?? null, fn (Builder $builder, string $method) => $builder->where('method', strtoupper($method)));
    }
}
