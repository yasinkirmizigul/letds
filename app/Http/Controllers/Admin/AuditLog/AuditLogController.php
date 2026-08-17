<?php

namespace App\Http\Controllers\Admin\AuditLog;

use App\Http\Controllers\Controller;
use App\Models\Admin\AuditLog\AuditLog;
use App\Models\Admin\User\User;
use App\Support\Audit\AuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();
        $mode = $request->string('mode', 'all')->toString();
        $q = trim((string) $request->get('q', ''));
        $action = trim((string) $request->get('action', ''));
        $status = trim((string) $request->get('status', ''));
        $method = strtoupper(trim((string) $request->get('method', '')));
        $perpage = max(10, min(200, (int) $request->get('perpage', 25)));

        $baseQuery = AuditLog::query()->visibleTo($actor);
        $query = (clone $baseQuery)->latest('id');

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
            'total' => (clone $baseQuery)->count(),
            'system' => $this->modeCount('system', $actor),
            'user' => $this->modeCount('user', $actor),
            'errors' => (clone $baseQuery)->where('status', '>=', 400)->count(),
            'slow' => (clone $baseQuery)->where('duration_ms', '>=', 1000)->count(),
        ];

        $actionOptions = (clone $baseQuery)
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

    public function show(Request $request, AuditLog $auditLog)
    {
        abort_unless($auditLog->isVisibleTo($request->user()), 404);

        return view('admin.pages.audit-logs.show', [
            'row' => $auditLog,
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $deletedCount = DB::transaction(
            fn (): int => AuditLog::query()->visibleTo($request->user())->delete()
        );

        AuditEvent::log('audit-logs.clear', [
            'deleted_count' => $deletedCount,
        ]);

        return redirect()
            ->route('admin.audit-logs.index')
            ->with('success', number_format($deletedCount).' eski log kaydı temizlendi. Güvenlik için bu işlemin kaydı tutuldu.');
    }

    private function modeCount(string $mode, User $actor): int
    {
        $query = AuditLog::query()->visibleTo($actor);
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
