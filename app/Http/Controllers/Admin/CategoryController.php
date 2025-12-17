<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
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
        $parentOptions = $this->treeOptions(); // tüm kategori ağacı

        return view('admin.pages.categories.create', [
            'pageTitle' => 'Kategori Oluştur',
            'parentOptions' => $parentOptions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $category = Category::create($data);

/*        $this->audit('created', $category, null, $category->only(['name','slug','parent_id']));*/

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori oluşturuldu.');
    }

    public function edit(Category $category)
    {
        // ✅ kendisi + tüm altları parent listesinde görünmesin
        $excludeIds = array_merge([$category->id], $category->descendantIds());
        $parentOptions = $this->treeOptions($excludeIds);

        return view('admin.pages.categories.edit', [
            'pageTitle' => 'Kategori Düzenle',
            'category' => $category,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category->id);

        // ✅ kendisi / altı parent olamaz
        $newParent = $data['parent_id'] ?? null;
        if ($newParent) {
            $invalidIds = array_merge([$category->id], $category->descendantIds());
            abort_if(in_array($newParent, $invalidIds, true), 422, 'Geçersiz üst kategori seçimi.');
        }

        $before = $category->only(['name','slug','parent_id']);

        $category->update($data);

        $this->audit('updated', $category, $before, $category->only(['name','slug','parent_id']));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori güncellendi.');
    }

    public function destroy(Category $category)
    {
        $before = $category->only(['name','slug','parent_id']);

        DB::transaction(function () use ($category) {
            // ✅ ilişki temizliği (şu an blog var)
            $category->blogPosts()->detach();

            // ✅ polymorphic pivot genel temizlik (ileride product/gallery eklesen bile çöp kalmaz)
            DB::table('categorizables')->where('category_id', $category->id)->delete();

            $category->delete();
        });

        // delete sonrası model instance durur (id vs duruyor), log atabiliriz
/*        $this->audit('deleted', $category, $before, null);*/

        return back()->with('success', 'Kategori silindi.');
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
