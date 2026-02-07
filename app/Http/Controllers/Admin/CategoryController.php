<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Admin\Category;
use App\Support\CategoryTree;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // -------------------------
    // Pages
    // -------------------------

    public function index(): \Illuminate\View\View
    {
        return view('admin.pages.categories.index', [
            'pageTitle' => 'Kategoriler',
            'mode' => 'active',
        ]);
    }

    public function trash(): \Illuminate\View\View
    {
        return view('admin.pages.categories.index', [
            'pageTitle' => 'Kategoriler',
            'mode' => 'trash',
        ]);
    }

    // -------------------------
    // JSON list (Media gibi)
    // GET /admin/categories/list?mode=active|trash&q=...&perpage=25&page=1
    // -------------------------

    public function listLegacy(Request $request)
    {
        $mode = $request->string('mode', 'active')->toString();
        $isTrash = $mode === 'trash';

        $q = trim($request->string('q', '')->toString());
        $perPage = max(1, min(200, (int) $request->input('perpage', 25)));

        $query = $isTrash
            ? Category::onlyTrashed()
            : Category::query();

        $query
            ->with(['parent:id,name'])
            ->withCount(['blogPosts'])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $items = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $items->getCollection()->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'parent_name' => $c->parent?->name,
                'blog_posts_count' => (int) ($c->blog_posts_count ?? 0),
                'deleted_at' => optional($c->deleted_at)->toISOString(),
                'edit_url'   => route('admin.categories.edit', $c),
                'delete_url' => route('admin.categories.destroy', $c),
                'restore_url' => route('admin.categories.restore', $c->id),
                'force_url'   => route('admin.categories.forceDestroy', $c->id),
            ])->values(),
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
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $q = Category::query()
            ->with('parent:id,name')
            ->withCount('blogPosts');

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $recordsTotal = Category::count();
        $recordsFiltered = (clone $q)->count();

        $items = $q->skip($start)->take($length)->get();

        $data = $items->map(function ($c) {
            return [
                'checkbox' => '<input type="checkbox" class="kt-checkbox" data-row-check value="'.$c->id.'">',
                'name' => e($c->name),
                'slug' => e($c->slug),
                'parent_name' => e(optional($c->parent)->name),
                'blog_posts_count' => (int) $c->blog_posts_count,
                'actions' => view('admin.pages.categories.partials._actions', compact('c'))->render(),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }


    // -------------------------
    // CRUD (senin mevcut yapın)
    // -------------------------

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
            ->with('success', 'Kategori oluşturuldu.');
    }

    public function edit(Category $category)
    {
        $all = CategoryTree::all();
        $byParent = CategoryTree::indexByParent($all);

        $descendantIds = CategoryTree::descendantIdsFromAll($category->id, $byParent);
        $excludeIds = array_merge([$category->id], $descendantIds);

        $parentOptions = CategoryTree::optionsFromIndex($byParent, $excludeIds);

        return view('admin.pages.categories.edit', [
            'pageTitle' => 'Kategori Düzenle',
            'category' => $category,
            'parentOptions' => $parentOptions,
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
            ->with('success', 'Kategori güncellendi.');
    }

    // -------------------------
    // Soft delete / trash ops
    // -------------------------

    public function destroy(Category $category)
    {
        $category->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori silindi.');
    }

    public function restore(int $id): JsonResponse
    {
        $cat = Category::onlyTrashed()->findOrFail($id);
        $cat->restore();

        return response()->json(['ok' => true]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $cat = Category::onlyTrashed()->findOrFail($id);

        $hasChildren = Category::withTrashed()->where('parent_id', $cat->id)->exists();
        if ($hasChildren) {
            return response()->json([
                'ok' => false,
                'message' => 'Bu kategorinin alt kategorileri var. Önce alt kategorileri taşıyın/silin.',
            ], 422);
        }

        $cat->forceDelete();

        return response()->json(['ok' => true]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $ids = array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
        if (!$ids) return response()->json(['ok' => true]);

        $hasChild = Category::whereIn('parent_id', $ids)->exists();
        if ($hasChild) {
            return response()->json([
                'ok' => false,
                'message' => 'Seçim içinde alt kategorisi olan kayıt var. Önce altları taşıyın/silin.',
            ], 422);
        }

        Category::whereIn('id', $ids)->delete();

        return response()->json(['ok' => true]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $ids = array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
        if (!$ids) return response()->json(['ok' => true]);

        Category::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json(['ok' => true]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $ids = array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
        if (!$ids) return response()->json(['ok' => true]);

        $hasChild = Category::withTrashed()->whereIn('parent_id', $ids)->exists();
        if ($hasChild) {
            return response()->json([
                'ok' => false,
                'message' => 'Seçim içinde alt kategorisi olan kayıt var. Önce altları taşıyın/silin.',
            ], 422);
        }

        Category::onlyTrashed()->whereIn('id', $ids)->forceDelete();

        return response()->json(['ok' => true]);
    }

    // -------------------------
    // Slug check (senin mevcut)
    // -------------------------

    public function checkSlug(Request $request)
    {
        $slug = trim((string) $request->query('slug', ''));
        $ignoreId = $request->integer('ignore');

        if ($slug === '') {
            return response()->json([
                'ok' => false,
                'available' => false,
                'message' => 'Slug boş olamaz.',
            ]);
        }

        $exists = Category::query()
            ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists();

        return response()->json([
            'ok' => true,
            'available' => !$exists,
            'message' => $exists ? 'Bu slug zaten kullanılıyor.' : 'Slug uygun.',
        ]);
    }

    public function trashList(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $q = Category::onlyTrashed()
            ->with('parent:id,name')
            ->withCount('blogPosts');

        $recordsTotal = Category::onlyTrashed()->count();
        $recordsFiltered = (clone $q)->count();

        $items = $q->skip($start)->take($length)->get();

        $data = $items->map(function ($c) {
            return [
                'name' => e($c->name),
                'slug' => e($c->slug),
                'parent_name' => e(optional($c->parent)->name),
                'deleted_at' => optional($c->deleted_at)->format('d.m.Y H:i'),
                'actions' => view('admin.pages.categories.partials._trash_actions', compact('c'))->render(),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
