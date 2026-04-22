<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Project\Project;
use App\Support\CategoryTree;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('admin.pages.categories.index', [
            'pageTitle' => 'Kategoriler',
            'mode' => 'active',
            'stats' => $this->stats(),
        ]);
    }

    public function trash(): \Illuminate\View\View
    {
        return view('admin.pages.categories.index', [
            'pageTitle' => 'Kategoriler',
            'mode' => 'trash',
            'stats' => $this->stats(),
        ]);
    }

    public function listLegacy(Request $request)
    {
        $mode = $request->string('mode', 'active')->toString();
        $isTrash = $mode === 'trash';
        $q = trim($request->string('q', '')->toString());
        $perPage = max(1, min(200, (int) $request->input('perpage', 25)));

        $query = $isTrash ? Category::onlyTrashed() : Category::query();

        $query
            ->with(['parent:id,name'])
            ->withCount(['blogPosts', 'children'])
            ->select('categories.*')
            ->selectSub(
                DB::table('categorizables')
                    ->selectRaw('count(*)')
                    ->whereColumn('category_id', 'categories.id')
                    ->where('categorizable_type', Project::class),
                'project_count'
            )
            ->selectSub(
                DB::table('category_product')
                    ->selectRaw('count(*)')
                    ->whereColumn('category_id', 'categories.id'),
                'product_count'
            )
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($nested) use ($q) {
                    $nested->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $items = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $items->getCollection()->map(function (Category $category) {
                $blogCount = (int) ($category->blog_posts_count ?? 0);
                $projectCount = (int) ($category->project_count ?? 0);
                $productCount = (int) ($category->product_count ?? 0);

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent_name' => $category->parent?->name,
                    'children_count' => (int) ($category->children_count ?? 0),
                    'blog_posts_count' => $blogCount,
                    'project_count' => $projectCount,
                    'product_count' => $productCount,
                    'content_count' => $blogCount + $projectCount + $productCount,
                    'deleted_at' => optional($category->deleted_at)->toISOString(),
                    'edit_url' => route('admin.categories.edit', $category),
                    'delete_url' => route('admin.categories.destroy', $category),
                    'restore_url' => route('admin.categories.restore', $category->id),
                    'force_url' => route('admin.categories.forceDestroy', $category->id),
                ];
            })->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function list(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $query = Category::query()
            ->with('parent:id,name')
            ->withCount('blogPosts');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $recordsTotal = Category::count();
        $recordsFiltered = (clone $query)->count();
        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($category) {
            return [
                'checkbox' => '<input type="checkbox" class="kt-checkbox" data-row-check value="' . $category->id . '">',
                'name' => e($category->name),
                'slug' => e($category->slug),
                'parent_name' => e(optional($category->parent)->name),
                'blog_posts_count' => (int) $category->blog_posts_count,
                'actions' => view('admin.pages.categories.partials._actions', ['c' => $category])->render(),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        $all = CategoryTree::all();
        $byParent = CategoryTree::indexByParent($all);

        return view('admin.pages.categories.create', [
            'pageTitle' => 'Yeni Kategori',
            'parentOptions' => CategoryTree::optionsFromIndex($byParent),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();

        Category::create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori olusturuldu.');
    }

    public function edit(Category $category)
    {
        $all = CategoryTree::all();
        $byParent = CategoryTree::indexByParent($all);
        $descendantIds = CategoryTree::descendantIdsFromAll($category->id, $byParent);
        $excludeIds = array_merge([$category->id], $descendantIds);

        return view('admin.pages.categories.edit', [
            'pageTitle' => 'Kategori Duzenle',
            'category' => $category,
            'parentOptions' => CategoryTree::optionsFromIndex($byParent, $excludeIds),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        $category->update([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori guncellendi.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Kategori cop kutusuna tasindi.']);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori silindi.');
    }

    public function restore(int $id): JsonResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return response()->json(['ok' => true, 'message' => 'Kategori geri yuklendi.']);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $guard = $this->guardCategoryForceDelete($category->id);

        if (!$guard['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $guard['message'],
            ], 422);
        }

        $category->forceDelete();

        return response()->json(['ok' => true, 'message' => 'Kategori kalici olarak silindi.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request->input('ids', []));
        if (count($ids) === 0) {
            return response()->json(['ok' => true, 'message' => 'Secili kayit yok.']);
        }

        $hasChild = Category::query()
            ->whereIn('parent_id', $ids)
            ->exists();

        if ($hasChild) {
            return response()->json([
                'ok' => false,
                'message' => 'Secim icinde alt kategorisi olan kayit var. Once alt kategorileri tasiyin veya silin.',
            ], 422);
        }

        Category::query()->whereIn('id', $ids)->delete();

        return response()->json(['ok' => true, 'message' => 'Secili kategoriler silindi.']);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request->input('ids', []));
        if (count($ids) === 0) {
            return response()->json(['ok' => true, 'message' => 'Secili kayit yok.']);
        }

        $count = Category::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json([
            'ok' => true,
            'message' => $count > 0 ? 'Secili kategoriler geri yuklendi.' : 'Geri yuklenecek kayit bulunamadi.',
        ]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request->input('ids', []));
        if (count($ids) === 0) {
            return response()->json(['ok' => true, 'message' => 'Secili kayit yok.']);
        }

        $blocked = [];
        foreach ($ids as $id) {
            $guard = $this->guardCategoryForceDelete($id);
            if (!$guard['ok']) {
                $blocked[] = [
                    'id' => $id,
                    'reason' => $guard['message'],
                ];
            }
        }

        $blockedIds = array_column($blocked, 'id');
        $allowedIds = array_values(array_diff($ids, $blockedIds));

        if (count($allowedIds) > 0) {
            Category::onlyTrashed()->whereIn('id', $allowedIds)->forceDelete();
        }

        return response()->json([
            'ok' => true,
            'done' => count($allowedIds),
            'failed' => array_map(fn ($item) => ['type' => 'category', 'id' => $item['id'], 'reason' => $item['reason']], $blocked),
            'message' => count($blocked) > 0
                ? 'Bazi kategoriler korunarak atlandi.'
                : 'Secili kategoriler kalici olarak silindi.',
        ]);
    }

    public function checkSlug(Request $request)
    {
        $slug = trim((string) $request->query('slug', ''));
        $ignoreId = $request->integer('ignore');

        if ($slug === '') {
            return response()->json([
                'ok' => false,
                'available' => false,
                'message' => 'Slug bos olamaz.',
            ]);
        }

        $exists = Category::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists();

        return response()->json([
            'ok' => true,
            'available' => !$exists,
            'message' => $exists ? 'Bu slug zaten kullaniliyor.' : 'Slug uygun.',
        ]);
    }

    public function trashList(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $query = Category::onlyTrashed()
            ->with('parent:id,name')
            ->withCount('blogPosts');

        $recordsTotal = Category::onlyTrashed()->count();
        $recordsFiltered = (clone $query)->count();
        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($category) {
            return [
                'name' => e($category->name),
                'slug' => e($category->slug),
                'parent_name' => e(optional($category->parent)->name),
                'deleted_at' => optional($category->deleted_at)->format('d.m.Y H:i'),
                'actions' => view('admin.pages.categories.partials._trash_actions', ['c' => $category])->render(),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function stats(): array
    {
        return [
            'total' => Category::query()->count(),
            'roots' => Category::query()->whereNull('parent_id')->count(),
            'blog_links' => DB::table('categorizables')->where('categorizable_type', BlogPost::class)->count(),
            'project_links' => DB::table('categorizables')->where('categorizable_type', Project::class)->count(),
            'product_links' => DB::table('category_product')->count(),
            'trash' => Category::onlyTrashed()->count(),
        ];
    }

    private function validatedIds($ids): array
    {
        return array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
    }

    private function guardCategoryForceDelete(int $categoryId): array
    {
        $hasChildren = Category::withTrashed()
            ->where('parent_id', $categoryId)
            ->exists();

        if ($hasChildren) {
            return ['ok' => false, 'message' => 'Bu kategorinin alt kategorileri var. Once alt kategorileri tasiyin veya silin.'];
        }

        $blogCount = DB::table('categorizables')
            ->where('category_id', $categoryId)
            ->where('categorizable_type', BlogPost::class)
            ->count();

        $projectCount = DB::table('categorizables')
            ->where('category_id', $categoryId)
            ->where('categorizable_type', Project::class)
            ->count();

        $productCount = DB::table('category_product')
            ->where('category_id', $categoryId)
            ->count();

        $totalUsage = $blogCount + $projectCount + $productCount;
        if ($totalUsage === 0) {
            return ['ok' => true, 'message' => ''];
        }

        $parts = [];
        if ($blogCount > 0) $parts[] = "blog: {$blogCount}";
        if ($projectCount > 0) $parts[] = "proje: {$projectCount}";
        if ($productCount > 0) $parts[] = "urun: {$productCount}";

        return [
            'ok' => false,
            'message' => 'Kategori iceriklere bagli. Once iliskileri kaldirin: ' . implode(', ', $parts),
        ];
    }
}
