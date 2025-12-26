<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Media\Media;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index()
    {
        return view('admin.pages.trash.index', [
            'pageTitle' => 'Silinenler',
        ]);
    }

    /**
     * GET /admin/trash/list?type=all|media|blog|category&page=1&perpage=25&q=
     *
     * Not: “all” için gerçek DB-level union yerine,
     * her modelden (perpage*page) kadar çekip PHP’de merge+sort+slicing yapıyoruz.
     * Admin panel için pratik, yeterince hızlı, az dosya dokunuşu.
     */
    public function list(Request $request): JsonResponse
    {
        $type = $request->string('type', 'all')->toString(); // all|media|blog|category
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

            // “all” modunda düzgün sıralama için deleted_at desc çekiyoruz
            $rows = $qb
                ->orderByDesc('deleted_at')
                ->limit($take)
                ->get();

            foreach ($rows as $row) {
                $items[] = ($cfg['map'])($row);
            }
        }

        // global sort
        usort($items, function ($a, $b) {
            return strcmp($b['deleted_at'] ?? '', $a['deleted_at'] ?? '');
        });

        // slice requested page
        $offset = ($page - 1) * $perPage;
        $pageItems = array_slice($items, $offset, $perPage);

        $lastPage = (int) max(1, (int) ceil($total / $perPage));

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

            if (!$id || !isset($sources[$type])) { $failed[] = $it; continue; }

            $perm = $sources[$type]['perm_restore'] ?? null;
            if ($perm && !$this->userCan($user, $perm)) { $denied[] = $it; continue; }

            try {
                $model = ($sources[$type]['model'])::onlyTrashed()->find($id);
                if (!$model) { $failed[] = $it; continue; }
                $model->restore();
                $done++;
            } catch (\Throwable $e) {
                $failed[] = $it;
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

            if (!$id || !isset($sources[$type])) { $failed[] = $it; continue; }

            $perm = $sources[$type]['perm_force'] ?? null;
            if ($perm && !$this->userCan($user, $perm)) { $denied[] = $it; continue; }

            try {
                $model = ($sources[$type]['model'])::onlyTrashed()->find($id);
                if (!$model) { $failed[] = $it; continue; }
                $model->forceDelete();
                $done++;
            } catch (\Throwable $e) {
                $failed[] = $it;
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
                    ];
                },
            ],
        ];
    }

    private function userCan($user, string $perm): bool
    {
        if (!$user) return false;
        if (method_exists($user, 'canAccess')) {
            return (bool) $user->canAccess($perm);
        }
        return (bool) $user->can($perm);
    }
}
