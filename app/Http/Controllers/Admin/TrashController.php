<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Media\Media;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Project\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrashController extends Controller
{
    public function index()
    {
        return view('admin.pages.trash.index', [
            'pageTitle' => 'Silinenler',
            'initialType' => request()->string('type', 'all')->toString(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $type = $request->string('type', 'all')->toString();
        $q = trim($request->string('q', '')->toString());

        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));
        $page = max(1, (int) $request->input('page', 1));
        $take = $perPage * $page;

        $sources = $this->sources();
        if ($type !== 'all' && !isset($sources[$type])) {
            return response()->json(['ok' => false, 'message' => 'type gecersiz'], 422);
        }

        $pick = ($type === 'all') ? array_keys($sources) : [$type];

        $total = 0;
        $items = [];

        foreach ($pick as $pickedType) {
            $config = $sources[$pickedType];
            $query = ($config['query'])();

            if ($q !== '') {
                $query->where(function ($builder) use ($config, $q) {
                    foreach (($config['search'] ?? []) as $column) {
                        $builder->orWhere($column, 'like', "%{$q}%");
                    }
                });
            }

            $total += (clone $query)->count();

            $rows = $query
                ->orderByDesc('deleted_at')
                ->limit($take)
                ->get();

            foreach ($rows as $row) {
                $items[] = ($config['map'])($row);
            }
        }

        usort($items, fn ($left, $right) => strcmp($right['deleted_at'] ?? '', $left['deleted_at'] ?? ''));

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

    public function restore(string $type, int $id): JsonResponse
    {
        $sources = $this->sources();

        if (!isset($sources[$type])) {
            return response()->json(['ok' => false, 'message' => 'type gecersiz'], 422);
        }

        $permission = $sources[$type]['perm_restore'] ?? null;
        if ($permission && !$this->userCan(auth()->user(), $permission)) {
            return response()->json(['ok' => false, 'message' => 'Yetki yok'], 403);
        }

        $modelClass = $sources[$type]['model'];
        $model = $modelClass::onlyTrashed()->find($id);

        if (!$model) {
            return response()->json(['ok' => false, 'message' => 'Kayit bulunamadi'], 404);
        }

        $model->restore();

        return response()->json(['ok' => true, 'message' => 'Kayit geri yuklendi.']);
    }

    public function forceDestroy(string $type, int $id): JsonResponse
    {
        $sources = $this->sources();

        if (!isset($sources[$type])) {
            return response()->json(['ok' => false, 'message' => 'type gecersiz'], 422);
        }

        $permission = $sources[$type]['perm_force'] ?? null;
        if ($permission && !$this->userCan(auth()->user(), $permission)) {
            return response()->json(['ok' => false, 'message' => 'Yetki yok'], 403);
        }

        $modelClass = $sources[$type]['model'];
        $model = $modelClass::onlyTrashed()->find($id);

        if (!$model) {
            return response()->json(['ok' => false, 'message' => 'Kayit bulunamadi'], 404);
        }

        $guard = $this->canForceDelete($type, $model);
        if (!($guard['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $guard['reason'] ?? 'Islem engellendi',
                'usage' => $guard['usage'] ?? null,
            ], 422);
        }

        $this->performForceDelete($type, $model);

        return response()->json(['ok' => true, 'message' => 'Kayit kalici olarak silindi.']);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['ok' => true, 'done' => 0, 'denied' => [], 'failed' => []]);
        }

        $sources = $this->sources();
        $user = auth()->user();

        $done = 0;
        $denied = [];
        $failed = [];

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');
            $id = (int) ($item['id'] ?? 0);

            if (!$id || !isset($sources[$type])) {
                $failed[] = $item;
                continue;
            }

            $permission = $sources[$type]['perm_restore'] ?? null;
            if ($permission && !$this->userCan($user, $permission)) {
                $denied[] = $item;
                continue;
            }

            try {
                $modelClass = $sources[$type]['model'];
                $model = $modelClass::onlyTrashed()->find($id);

                if (!$model) {
                    $failed[] = $item;
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

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['ok' => true, 'done' => 0, 'denied' => [], 'failed' => []]);
        }

        $sources = $this->sources();
        $user = auth()->user();

        $done = 0;
        $denied = [];
        $failed = [];

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');
            $id = (int) ($item['id'] ?? 0);

            if (!$id || !isset($sources[$type])) {
                $failed[] = $item;
                continue;
            }

            $permission = $sources[$type]['perm_force'] ?? null;
            if ($permission && !$this->userCan($user, $permission)) {
                $denied[] = $item;
                continue;
            }

            try {
                $model = ($sources[$type]['model'])::onlyTrashed()->find($id);
                if (!$model) {
                    $failed[] = $item;
                    continue;
                }

                $guard = $this->canForceDelete($type, $model);
                if (!($guard['ok'] ?? false)) {
                    $failed[] = [
                        'type' => $type,
                        'id' => $id,
                        'reason' => $guard['reason'] ?? 'Islem engellendi',
                    ];
                    continue;
                }

                $this->performForceDelete($type, $model);
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
                'query' => fn () => Media::onlyTrashed(),
                'search' => ['original_name', 'title', 'alt'],
                'perm_restore' => 'media.restore',
                'perm_force' => 'media.force_delete',
                'map' => function (Media $media) {
                    return [
                        'type' => 'media',
                        'id' => $media->id,
                        'title' => $media->title ?: ($media->original_name ?: 'Media'),
                        'sub' => $media->mime_type ?: '',
                        'deleted_at' => optional($media->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'media', 'id' => $media->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'media', 'id' => $media->id]),
                    ];
                },
            ],

            'blog' => [
                'model' => BlogPost::class,
                'query' => fn () => BlogPost::onlyTrashed(),
                'search' => ['title', 'slug'],
                'perm_restore' => 'blog.restore',
                'perm_force' => 'blog.force_delete',
                'map' => function (BlogPost $post) {
                    return [
                        'type' => 'blog',
                        'id' => $post->id,
                        'title' => $post->title ?: 'Blog',
                        'sub' => $post->slug ?: '',
                        'deleted_at' => optional($post->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'blog', 'id' => $post->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'blog', 'id' => $post->id]),
                    ];
                },
            ],

            'category' => [
                'model' => Category::class,
                'query' => fn () => Category::onlyTrashed(),
                'search' => ['name', 'slug'],
                'perm_restore' => 'categories.restore',
                'perm_force' => 'categories.force_delete',
                'map' => function (Category $category) {
                    return [
                        'type' => 'category',
                        'id' => $category->id,
                        'title' => $category->name ?: 'Kategori',
                        'sub' => $category->slug ?: '',
                        'deleted_at' => optional($category->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'category', 'id' => $category->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'category', 'id' => $category->id]),
                    ];
                },
            ],

            'project' => [
                'model' => Project::class,
                'query' => fn () => Project::onlyTrashed(),
                'search' => ['title', 'slug'],
                'perm_restore' => 'projects.restore',
                'perm_force' => 'projects.force_delete',
                'map' => function (Project $project) {
                    return [
                        'type' => 'project',
                        'id' => $project->id,
                        'title' => $project->title ?: 'Proje',
                        'sub' => $project->slug ?: '',
                        'deleted_at' => optional($project->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'project', 'id' => $project->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'project', 'id' => $project->id]),
                    ];
                },
            ],

            'product' => [
                'model' => Product::class,
                'query' => fn () => Product::onlyTrashed(),
                'search' => ['title', 'slug', 'sku', 'barcode'],
                'perm_restore' => 'products.restore',
                'perm_force' => 'products.force_delete',
                'map' => function (Product $product) {
                    return [
                        'type' => 'product',
                        'id' => $product->id,
                        'title' => $product->title ?: 'Urun',
                        'sub' => $product->sku ?: ($product->slug ?: ''),
                        'deleted_at' => optional($product->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'product', 'id' => $product->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'product', 'id' => $product->id]),
                    ];
                },
            ],

            'gallery' => [
                'model' => Gallery::class,
                'query' => fn () => Gallery::onlyTrashed(),
                'search' => ['name', 'slug', 'description'],
                'perm_restore' => 'galleries.restore',
                'perm_force' => 'galleries.force_delete',
                'map' => function (Gallery $gallery) {
                    return [
                        'type' => 'gallery',
                        'id' => $gallery->id,
                        'title' => $gallery->name ?: 'Galeri',
                        'sub' => $gallery->slug ?: '',
                        'deleted_at' => optional($gallery->deleted_at)->toISOString(),
                        'restore_url' => route('admin.trash.restoreOne', ['type' => 'gallery', 'id' => $gallery->id]),
                        'force_url' => route('admin.trash.forceDestroyOne', ['type' => 'gallery', 'id' => $gallery->id]),
                    ];
                },
            ],
        ];
    }

    private function userCan($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'canAccess')) {
            return (bool) $user->canAccess($permission);
        }

        return (bool) $user->can($permission);
    }

    private function canForceDelete(string $type, $model): array
    {
        if ($type === 'media') {
            /** @var \App\Models\Admin\Media\Media $model */
            $usage = $this->mediaUsageReportDynamic('media', 'id', (int) $model->id);
            $usage = $this->mergeUsage($usage, $this->mediaUsageReportManual((int) $model->id));

            if (($usage['total'] ?? 0) > 0) {
                return [
                    'ok' => false,
                    'reason' => 'Bu medya kullaniliyor. Once iliskileri kaldirin: ' . ($usage['summary'] ?? ''),
                    'usage' => $usage,
                ];
            }

            return ['ok' => true];
        }

        if ($type === 'category') {
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

            $hasProject = DB::table('categorizables')
                ->where('category_id', $model->id)
                ->where('categorizable_type', Project::class)
                ->exists();

            $hasProduct = DB::table('category_product')
                ->where('category_id', $model->id)
                ->exists();

            if ($hasBlog || $hasProject || $hasProduct) {
                return ['ok' => false, 'reason' => 'Kategori iceriklere bagli'];
            }
        }

        if ($type === 'gallery') {
            $attachedCount = (int) DB::table('galleryables')
                ->where('gallery_id', $model->id)
                ->count();

            if ($attachedCount > 0) {
                return ['ok' => false, 'reason' => 'Bu galeri iceriklere bagli'];
            }
        }

        return ['ok' => true];
    }

    private function performForceDelete(string $type, $model): void
    {
        DB::transaction(function () use ($type, $model) {
            if ($type === 'blog') {
                DB::table('categorizables')
                    ->where('categorizable_type', BlogPost::class)
                    ->where('categorizable_id', $model->id)
                    ->delete();

                DB::table('mediables')
                    ->where('mediable_type', BlogPost::class)
                    ->where('mediable_id', $model->id)
                    ->delete();

                DB::table('galleryables')
                    ->where('galleryable_type', BlogPost::class)
                    ->where('galleryable_id', $model->id)
                    ->delete();

                $this->deleteLegacyImage($model->featured_image_path ?? null);
                $model->forceDelete();
                return;
            }

            if ($type === 'project') {
                DB::table('categorizables')
                    ->where('categorizable_type', Project::class)
                    ->where('categorizable_id', $model->id)
                    ->delete();

                DB::table('mediables')
                    ->where('mediable_type', Project::class)
                    ->where('mediable_id', $model->id)
                    ->delete();

                DB::table('galleryables')
                    ->where('galleryable_type', Project::class)
                    ->where('galleryable_id', $model->id)
                    ->delete();

                $this->deleteLegacyImage($model->featured_image_path ?? null);
                $model->forceDelete();
                return;
            }

            if ($type === 'product') {
                DB::table('category_product')
                    ->where('product_id', $model->id)
                    ->delete();

                DB::table('mediables')
                    ->where('mediable_type', Product::class)
                    ->where('mediable_id', $model->id)
                    ->delete();

                DB::table('galleryables')
                    ->where('galleryable_type', Product::class)
                    ->where('galleryable_id', $model->id)
                    ->delete();

                $this->deleteLegacyImage($model->featured_image_path ?? null);
                $model->forceDelete();
                return;
            }

            if ($type === 'gallery') {
                DB::table('gallery_items')
                    ->where('gallery_id', $model->id)
                    ->delete();

                $model->forceDelete();
                return;
            }

            $model->forceDelete();
        });
    }

    private function deleteLegacyImage(?string $path): void
    {
        $resolved = trim((string) $path);
        if ($resolved === '') {
            return;
        }

        Storage::disk('public')->delete($resolved);
    }

    private function mediaUsageReportDynamic(string $parentTable, string $parentColumn, int $parentId): array
    {
        $database = DB::getDatabaseName();

        $rows = DB::select(
            "SELECT
                kcu.TABLE_NAME AS table_name,
                kcu.COLUMN_NAME AS column_name,
                kcu.CONSTRAINT_NAME AS constraint_name
             FROM information_schema.KEY_COLUMN_USAGE kcu
             WHERE kcu.TABLE_SCHEMA = ?
               AND kcu.REFERENCED_TABLE_NAME = ?
               AND kcu.REFERENCED_COLUMN_NAME = ?",
            [$database, $parentTable, $parentColumn]
        );

        $details = [];
        $total = 0;

        foreach ($rows as $row) {
            $table = (string) $row->table_name;
            $column = (string) $row->column_name;

            try {
                $count = (int) DB::table($table)->where($column, $parentId)->count();
            } catch (\Throwable $e) {
                $count = 0;
            }

            if ($count > 0) {
                $label = $this->humanizeTableName($table, $column);
                $details[$label] = $count;
                $total += $count;
            }
        }

        $summary = $details
            ? collect($details)->map(fn ($value, $key) => "{$key}: {$value}")->implode(', ')
            : '';

        return [
            'total' => $total,
            'details' => $details,
            'summary' => $summary,
            'raw' => collect($rows)->map(fn ($row) => ['table' => $row->table_name, 'col' => $row->column_name])->values()->all(),
        ];
    }

    private function humanizeTableName(string $table, string $column): string
    {
        $map = [
            'gallery_items' => 'Galeri ogeleri',
        ];

        return $map[$table] ?? "{$table}.{$column}";
    }

    private function mediaUsageReportManual(int $mediaId): array
    {
        $details = [];
        $total = 0;

        $summary = $details
            ? collect($details)->map(fn ($value, $key) => "{$key}: {$value}")->implode(', ')
            : '';

        return [
            'total' => $total,
            'details' => $details,
            'summary' => $summary,
        ];
    }

    private function mergeUsage(array $left, array $right): array
    {
        $details = array_merge($left['details'] ?? [], $right['details'] ?? []);
        $total = (int) ($left['total'] ?? 0) + (int) ($right['total'] ?? 0);

        $summary = $details
            ? collect($details)->map(fn ($value, $key) => "{$key}: {$value}")->implode(', ')
            : '';

        return [
            'total' => $total,
            'details' => $details,
            'summary' => $summary,
        ];
    }
}
