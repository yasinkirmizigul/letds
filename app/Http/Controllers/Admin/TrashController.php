<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Project\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrashController extends Controller
{
    public function index()
    {
        return view('admin.pages.trash.index', [
            'pageTitle' => 'Silinenler',
        ]);
    }

    /**
     * GET /admin/trash/list?type=all|media|blog|category|project&page=1&perpage=25&q=
     */
    public function list(Request $request): JsonResponse
    {
        $type = $request->string('type', 'all')->toString();
        $q = trim($request->string('q', '')->toString());

        $perPage = max(1, min(100, (int)$request->input('perpage', 25)));
        $page = max(1, (int)$request->input('page', 1));
        $take = $perPage * $page;

        $sources = $this->sources();
        if ($type !== 'all' && !isset($sources[$type])) {
            return response()->json(['ok' => false, 'message' => 'type geçersiz'], 422);
        }

        $pick = ($type === 'all') ? array_keys($sources) : [$type];

        $total = 0;
        $items = [];

        foreach ($pick as $t) {
            $cfg = $sources[$t];

            /** @var \Illuminate\Database\Eloquent\Builder $qb */
            $qb = ($cfg['query'])();

            if ($q !== '') {
                $qb->where(function ($w) use ($cfg, $q) {
                    foreach (($cfg['search'] ?? []) as $col) {
                        $w->orWhere($col, 'like', "%{$q}%");
                    }
                });
            }

            $total += (clone $qb)->count();

            $rows = $qb
                ->orderByDesc('deleted_at')
                ->limit($take)
                ->get();

            foreach ($rows as $row) {
                $items[] = ($cfg['map'])($row);
            }
        }

        usort($items, function ($a, $b) {
            return strcmp($b['deleted_at'] ?? '', $a['deleted_at'] ?? '');
        });

        $offset = ($page - 1) * $perPage;
        $pageItems = array_slice($items, $offset, $perPage);

        $lastPage = (int)max(1, (int)ceil($total / $perPage));

        return response()->json([
            'ok' => true,
            'data' => array_values($pageItems),
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Tekil RESTORE (satır butonu)
     * POST /admin/trash/{type}/{id}/restore
     */
    public function restore(string $type, int $id): JsonResponse
    {
        $sources = $this->sources();

        if (!isset($sources[$type])) {
            return response()->json(['ok' => false, 'message' => 'type geçersiz'], 422);
        }

        $perm = $sources[$type]['perm_restore'] ?? null;
        if ($perm && !$this->userCan(auth()->user(), $perm)) {
            return response()->json(['ok' => false, 'message' => 'Yetki yok'], 403);
        }

        $modelClass = $sources[$type]['model'];
        $model = $modelClass::onlyTrashed()->find($id);

        if (!$model) {
            return response()->json(['ok' => false, 'message' => 'Kayıt bulunamadı'], 404);
        }

        $model->restore();

        return response()->json(['ok' => true]);
    }

    /**
     * Tekil FORCE DELETE (satır butonu)
     * DELETE /admin/trash/{type}/{id}
     */
    public function forceDestroy(string $type, int $id): JsonResponse
    {
        $sources = $this->sources();

        if (!isset($sources[$type])) {
            return response()->json(['ok' => false, 'message' => 'type geçersiz'], 422);
        }

        $perm = $sources[$type]['perm_force'] ?? null;
        if ($perm && !$this->userCan(auth()->user(), $perm)) {
            return response()->json(['ok' => false, 'message' => 'Yetki yok'], 403);
        }

        $modelClass = $sources[$type]['model'];
        $model = $modelClass::onlyTrashed()->find($id);

        if (!$model) {
            return response()->json(['ok' => false, 'message' => 'Kayıt bulunamadı'], 404);
        }

        $guard = $this->canForceDelete($type, $model);
        if (!($guard['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $guard['reason'] ?? 'İşlem engellendi',
                'usage' => $guard['usage'] ?? null,
            ], 422);
        }

        $model->forceDelete();

        return response()->json(['ok' => true]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        if (!is_array($items) || !count($items)) {
            return response()->json(['ok' => true, 'done' => 0, 'denied' => [], 'failed' => []]);
        }

        $sources = $this->sources();
        $user = auth()->user();

        $done = 0;
        $denied = [];
        $failed = [];

        foreach ($items as $it) {
            $type = (string)($it['type'] ?? '');
            $id = (int)($it['id'] ?? 0);

            if (!$id || !isset($sources[$type])) {
                $failed[] = $it;
                continue;
            }

            $perm = $sources[$type]['perm_restore'] ?? null;
            if ($perm && !$this->userCan($user, $perm)) {
                $denied[] = $it;
                continue;
            }

            try {
                $modelClass = $sources[$type]['model'];
                $model = $modelClass::onlyTrashed()->find($id);

                if (!$model) {
                    $failed[] = $it;
                    continue;
                }

                $model->restore();
                $done++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'type' => $type,
                    'id' => $id,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'done' => $done,
            'denied' => $denied,
            'failed' => $failed,
        ]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        if (!is_array($items) || !count($items)) {
            return response()->json(['ok' => true, 'done' => 0, 'denied' => [], 'failed' => []]);
        }

        $sources = $this->sources();
        $user = auth()->user();

        $done = 0;
        $denied = [];
        $failed = [];

        foreach ($items as $it) {
            $type = (string)($it['type'] ?? '');
            $id = (int)($it['id'] ?? 0);

            if (!$id || !isset($sources[$type])) {
                $failed[] = $it;
                continue;
            }

            $perm = $sources[$type]['perm_force'] ?? null;
            if ($perm && !$this->userCan($user, $perm)) {
                $denied[] = $it;
                continue;
            }

            try {
                $model = ($sources[$type]['model'])::onlyTrashed()->find($id);
                if (!$model) {
                    $failed[] = $it;
                    continue;
                }

                $guard = $this->canForceDelete($type, $model);
                if (!($guard['ok'] ?? false)) {
                    $failed[] = [
                        'type' => $type,
                        'id' => $id,
                        'reason' => $guard['reason'] ?? 'İşlem engellendi',
                    ];
                    continue;
                }

                $model->forceDelete();
                $done++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'type' => $type,
                    'id' => $id,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'done' => $done,
            'denied' => $denied,
            'failed' => $failed,
        ]);
    }

    private function sources(): array
    {
        return [
            'media' => [
                'model' => Media::class,
                'query' => fn() => Media::onlyTrashed(),
                'search' => ['original_name', 'title', 'alt'],
                'perm_restore' => 'media.restore',
                'perm_force' => 'media.force_delete',
                'map' => function (Media $m) {
                    return [
                        'type' => 'media',
                        'id' => $m->id,
                        'title' => $m->title ?: ($m->original_name ?: 'Media'),
                        'sub' => $m->mime_type ?: '',
                        'deleted_at' => optional($m->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'media', 'id' => $m->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'media', 'id' => $m->id]),
                    ];
                },
            ],

            'blog' => [
                'model' => BlogPost::class,
                'query' => fn() => BlogPost::onlyTrashed(),
                'search' => ['title', 'slug'],
                'perm_restore' => 'blog.restore',
                'perm_force' => 'blog.force_delete',
                'map' => function (BlogPost $p) {
                    return [
                        'type' => 'blog',
                        'id' => $p->id,
                        'title' => $p->title ?: 'Blog',
                        'sub' => $p->slug ?: '',
                        'deleted_at' => optional($p->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'blog', 'id' => $p->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'blog', 'id' => $p->id]),
                    ];
                },
            ],

            'category' => [
                'model' => Category::class,
                'query' => fn() => Category::onlyTrashed(),
                'search' => ['name', 'slug'],
                'perm_restore' => 'category.restore',
                'perm_force' => 'category.force_delete',
                'map' => function (Category $c) {
                    return [
                        'type' => 'category',
                        'id' => $c->id,
                        'title' => $c->name ?: 'Kategori',
                        'sub' => $c->slug ?: '',
                        'deleted_at' => optional($c->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'category', 'id' => $c->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'category', 'id' => $c->id]),
                    ];
                },
            ],

            'project' => [
                'model' => Project::class,
                'query' => fn() => Project::onlyTrashed(),
                'search' => ['title', 'slug'],
                'perm_restore' => 'project.update',
                'perm_force' => 'project.delete',
                'map' => function (Project $p) {
                    return [
                        'type' => 'project',
                        'id' => $p->id,
                        'title' => $p->title ?: 'Proje',
                        'sub' => $p->slug ?: '',
                        'deleted_at' => optional($p->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'project', 'id' => $p->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'project', 'id' => $p->id]),
                    ];
                },
            ],
        ];
    }

    private function userCan($user, string $perm): bool
    {
        if (!$user) return false;

        if (method_exists($user, 'canAccess')) {
            return (bool)$user->canAccess($perm);
        }

        return (bool)$user->can($perm);
    }

    private function canForceDelete(string $type, $model): array
    {
        // ✅ Media: FK / kullanım kontrolü (SQL exception'a düşmeden engelle)
        if ($type === 'media') {
            /** @var \App\Models\Admin\Media\Media $model */
            $usage = $this->mediaUsageReportDynamic('media', 'id', (int)$model->id);

            // FK olmayan (polymorphic/json vs) kullanım varsa burayı doldur
            $usage = $this->mergeUsage($usage, $this->mediaUsageReportManual((int)$model->id));

            if (($usage['total'] ?? 0) > 0) {
                return [
                    'ok' => false,
                    'reason' => 'Bu medya kullanılıyor. Önce ilişkileri kaldırın: ' . ($usage['summary'] ?? ''),
                    'usage' => $usage,
                ];
            }

            return ['ok' => true];
        }

        // default allow
        if ($type !== 'category') {
            return ['ok' => true];
        }

        /** @var \App\Models\Admin\Category $model */
        $hasChild = Category::withTrashed()
            ->where('parent_id', $model->id)
            ->exists();

        if ($hasChild) {
            return ['ok' => false, 'reason' => 'Alt kategori var'];
        }

        $hasBlog = DB::table('categorizables')
            ->where('category_id', $model->id)
            ->where('categorizable_type', BlogPost::class)
            ->exists();

        if ($hasBlog) {
            return ['ok' => false, 'reason' => 'Blog yazılarına bağlı'];
        }

        return ['ok' => true];
    }

    /**
     * DB'deki tüm FK'leri tarar: media.id'yi referanslayan tabloları otomatik bulur.
     */
    private function mediaUsageReportDynamic(string $parentTable, string $parentColumn, int $parentId): array
    {
        $db = DB::getDatabaseName();

        $rows = DB::select(
            "SELECT
                kcu.TABLE_NAME AS table_name,
                kcu.COLUMN_NAME AS column_name,
                kcu.CONSTRAINT_NAME AS constraint_name
             FROM information_schema.KEY_COLUMN_USAGE kcu
             WHERE kcu.TABLE_SCHEMA = ?
               AND kcu.REFERENCED_TABLE_NAME = ?
               AND kcu.REFERENCED_COLUMN_NAME = ?",
            [$db, $parentTable, $parentColumn]
        );

        $details = [];
        $total = 0;

        foreach ($rows as $r) {
            $table = (string)$r->table_name;
            $col = (string)$r->column_name;

            try {
                $cnt = (int)DB::table($table)->where($col, $parentId)->count();
            } catch (\Throwable $e) {
                $cnt = 0;
            }

            if ($cnt > 0) {
                $label = $this->humanizeTableName($table, $col);
                $details[$label] = $cnt;
                $total += $cnt;
            }
        }

        $summary = $details
            ? collect($details)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ')
            : '';

        return [
            'total' => $total,
            'details' => $details,
            'summary' => $summary,
            'raw' => collect($rows)->map(fn($x) => ['table' => $x->table_name, 'col' => $x->column_name])->values()->all(),
        ];
    }

    private function humanizeTableName(string $table, string $column): string
    {
        $map = [
            'gallery_items' => 'Galeri öğeleri',
            // tablo isimlerini istersen burada TR label'a çevir
        ];

        return $map[$table] ?? "{$table}.{$column}";
    }

    /**
     * FK olmayan kullanım (polymorphic/json/pivot vs) varsa buraya ekle.
     * Şu an boş: sadece dinamik FK kontrolü çalışıyor.
     */
    private function mediaUsageReportManual(int $mediaId): array
    {
        $details = [];
        $total = 0;

        // ÖRN:
        // $cnt = (int) DB::table('galleryables')->where('media_id', $mediaId)->count();
        // if ($cnt) { $details['Galeri ilişkilendirmeleri'] = $cnt; $total += $cnt; }

        $summary = $details
            ? collect($details)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ')
            : '';

        return [
            'total' => $total,
            'details' => $details,
            'summary' => $summary,
        ];
    }

    private function mergeUsage(array $a, array $b): array
    {
        $details = array_merge($a['details'] ?? [], $b['details'] ?? []);
        $total = (int)($a['total'] ?? 0) + (int)($b['total'] ?? 0);

        $summary = $details
            ? collect($details)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ')
            : '';

        return [
            'total' => $total,
            'details' => $details,
            'summary' => $summary,
        ];
    }
}
