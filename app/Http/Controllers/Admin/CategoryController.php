<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Admin\Category;
use App\Support\CategoryTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->with(['parent:id,name'])
            ->withCount(['blogPosts'])
            ->orderBy('name')
            ->get(['id','name','slug','parent_id','created_at']);

        return view('admin.pages.categories.index', [
            'pageTitle' => 'Kategoriler',
            'categories' => $categories,
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
            ->with('success', 'Kategori oluşturuldu.');
    }

    public function edit(Category $category)
    {
        // 1) Tek query
        $all = CategoryTree::all();

        // 2) RAM index
        $byParent = CategoryTree::indexByParent($all);

        // 3) RAM’den descendant id’leri
        $descendantIds = CategoryTree::descendantIdsFromAll($category->id, $byParent);

        // 4) kendisi + tüm altları exclude
        $excludeIds = array_merge([$category->id], $descendantIds);

        // 5) parent options
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


    public function destroy(Category $category)
    {
        $hasChildren = Category::where('parent_id', $category->id)->exists();

        if ($hasChildren) {
            return back()->with('error', 'Bu kategorinin alt kategorileri var. Önce alt kategorileri taşıyın veya silin.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori silindi.');
    }

    /**
     * ✅ Live slug check
     * GET admin/categories/check-slug?slug=...&ignore=ID(optional)
     */
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

    // -------------------------
    // Internals
    // -------------------------

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:120'],
            'slug' => [
                'required','string','max:160',
                Rule::unique('categories','slug')->ignore($ignoreId),
            ],
            'parent_id' => ['nullable','integer','exists:categories,id'],
        ]);
    }

    /**
     * Parent select için hiyerarşik option listesi üretir.
     * excludeIds: edit ekranında kendisi + altları gibi hariç tutulacaklar.
     *
     * Output: [ ['id'=>1,'label'=>'A'], ['id'=>2,'label'=>'— B'], ... ]
     */
    private function treeOptions(array $excludeIds = []): array
    {
        $all = Category::query()
            ->orderBy('name')
            ->get(['id','name','parent_id']);

        $byParent = [];
        foreach ($all as $c) {
            $byParent[$c->parent_id ?? 0][] = $c;
        }

        $out = [];
        $walk = function ($parentId, $depth) use (&$walk, &$out, $byParent, $excludeIds) {
            foreach (($byParent[$parentId] ?? []) as $c) {
                if (in_array($c->id, $excludeIds, true)) {
                    continue;
                }

                $out[] = [
                    'id' => $c->id,
                    'label' => str_repeat('— ', $depth) . $c->name,
                ];

                $walk($c->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $out;
    }

/*    private function audit(string $action, Category $category, ?array $before, ?array $after): void
    {
        // Audit log tablon yoksa bu satırı kaldırırsın; varsa “kurumsal kalite” budur.
        CategoryAuditLog::create([
            'category_id' => $category->id,
            'user_id' => auth()->id(),
            'action' => $action, // created/updated/deleted
            'before' => $before,
            'after' => $after,
            'ip' => request()->ip(),
        ]);
    }*/
}
