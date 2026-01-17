<?php

namespace App\Http\Controllers\Admin\BlogPost;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->string('mode', 'active')->toString(); // active|trash
        $isTrash = $mode === 'trash';

        $q = $request->string('q')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        // ✅ seçili kategoriler (GET: category_ids[])
        $selectedCategoryIds = collect($request->input('category_ids', []))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();

        // ✅ kategori options (view blogCategoryFilter bunu istiyor)
        $categories = \App\Models\Admin\Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
        $categoryOptions = $this->categoryOptions($categories);

        $query = $isTrash ? BlogPost::onlyTrashed() : BlogPost::query();

        $posts = $query
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            // ✅ kategori filtresi (relation varsa)
            ->when(!empty($selectedCategoryIds) && method_exists(BlogPost::class, 'categories'), function ($qq) use ($selectedCategoryIds) {
                $qq->whereHas('categories', function ($c) use ($selectedCategoryIds) {
                    $c->whereIn('categories.id', $selectedCategoryIds);
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.blog.index', [
            'mode' => $isTrash ? 'trash' : 'active',
            'posts' => $posts,
            'q' => $q,
            'perPage' => $perPage,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => $selectedCategoryIds,
            'pageTitle' => 'Blog',
        ]);
    }


    public function trash(Request $request)
    {
        // aynı index view, sadece mode
        $request->merge(['mode' => 'trash']);
        return $this->index($request);
    }

    /**
     * Opsiyonel JSON list endpoint (mode destekli)
     * /admin/blog/list?mode=trash&q=...&perpage=25&page=1
     */
    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();
        $q = $request->string('q')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $query = $mode === 'trash'
            ? BlogPost::onlyTrashed()->latest('id')
            : BlogPost::query()->latest('id');

        $items = $query
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    // --- CRUD: Bunlar sende zaten vardı. Varsa kendi mevcut create/store/edit/update'ini koru. ---
    public function create()
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $categoryOptions = $this->categoryOptions($categories);

        return view('admin.pages.blog.create', [
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => [],
            'pageTitle' => 'Yazı Ekle',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'slug'  => ['nullable','string','max:255'],
            'content' => ['nullable','string'],
            'category_ids' => ['nullable','array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'featured_media_id' => ['nullable', 'integer'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug);

        $post = BlogPost::create($data);
        // ✅ Featured: önce upload varsa legacy path’e kaydet (fallback), sonra featured_media_id set edildiyse pivot’a yaz
        if ($request->hasFile('featured_image')) {
            // legacy path (istersen ileride kaldırırsın)
            if ($post->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
            }
            $path = $request->file('featured_image')->store('blog/featured', 'public');
            $post->featured_image_path = $path;
            $post->save();

            // upload seçilince media_id sıfırlansın (tek kaynak seç)
            $this->syncFeaturedMedia($post, null);
        } else {
            $this->syncFeaturedMedia($post, $request->input('featured_media_id') ? (int)$request->input('featured_media_id') : null);
        }

        if (method_exists($post, 'categories')) {
            $ids = collect($data['category_ids'] ?? [])
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $post->categories()->sync($ids);
        }

        return redirect()->route('admin.blog.index')->with('success', 'Blog yazısı oluşturuldu.');
    }

    public function edit(BlogPost $blogPost)
    {
        // edit ekranında seçili kategoriler görünmesi için
        // (Category ilişkinde withTrashed yoksa bile en azından burada yakalamaya çalışırız)
        try {
            $blogPost->load(['categories' => fn ($q) => $q->withTrashed()]);
        } catch (\Throwable $e) {
            $blogPost->load('categories');
        }

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $categoryOptions = $this->categoryOptions($categories);

        $selectedCategoryIds = $blogPost->categories
            ? $blogPost->categories->pluck('id')->map(fn ($v) => (int) $v)->values()->all()
            : [];

        return view('admin.pages.blog.edit', [
            'blogPost' => $blogPost,
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => $selectedCategoryIds,
            'pageTitle' => 'Yazı Düzenle',
        ]);
    }

    public function update(Request $request, BlogPost $blogPost)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'slug'  => ['nullable','string','max:255'],
            'content' => ['nullable','string'],
            'category_ids' => ['nullable','array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'featured_media_id' => ['nullable', 'integer'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug, $blogPost->id);

        $blogPost->update($data);

        if ($request->hasFile('featured_image')) {

            if ($blogPost->featured_image_path) {
                Storage::disk('public')->delete($blogPost->featured_image_path);
            }

            $path = $request->file('featured_image')->store('blog/featured', 'public');
            $blogPost->featured_image_path = $path;
            $blogPost->save();

            // upload -> media pivot temizle
            $this->syncFeaturedMedia($blogPost, null);

        } else {
            // library seçimi -> pivot güncelle
            $featuredMediaId = $request->input('featured_media_id');
            $this->syncFeaturedMedia($blogPost, $featuredMediaId ? (int)$featuredMediaId : null);
        }

        if (method_exists($blogPost, 'categories')) {
            $ids = collect($data['category_ids'] ?? [])
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $blogPost->categories()->sync($ids);
        }

        return redirect()->route('admin.blog.index')->with('success', 'Blog yazısı güncellendi.');
    }

    // --- Soft delete (single) ---
    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $blogPost->delete(); // soft delete
        return response()->json(['ok' => true]);
    }

    // --- Restore (single) ---
    public function restore(int $id): JsonResponse
    {
        $post = BlogPost::onlyTrashed()->findOrFail($id);
        $post->restore();

        return response()->json(['ok' => true, 'data' => ['restored' => true]]);
    }

    // --- Force delete (single) ---
    public function forceDestroy(int $id): JsonResponse
    {
        $post = BlogPost::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($post) {
            // kategori pivot temizliği (ilişki adın farklıysa düzelt)
            if (method_exists($post, 'categories')) {
                $post->categories()->detach();
            }

            $post->forceDelete();
        });

        return response()->json(['ok' => true, 'data' => ['force_deleted' => true]]);
    }

    // --- Bulk soft delete ---
    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = BlogPost::query()->whereIn('id', $ids)->delete(); // soft delete

        return response()->json(['ok' => true, 'data' => ['deleted' => $count]]);
    }

    // --- Bulk restore ---
    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = BlogPost::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json(['ok' => true, 'data' => ['restored' => $count]]);
    }

    // --- Bulk force delete ---
    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $posts = BlogPost::withTrashed()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($posts) {
            foreach ($posts as $post) {
                if (method_exists($post, 'categories')) {
                    $post->categories()->detach();
                }
                $post->forceDelete();
            }
        });

        return response()->json(['ok' => true, 'data' => ['force_deleted' => $posts->count()]]);
    }

    public function togglePublish(BlogPost $blogPost): JsonResponse
    {
        $blogPost->is_published = !$blogPost->is_published;
        $blogPost->save();

        return response()->json(['ok' => true, 'data' => ['is_published' => (bool) $blogPost->is_published]]);
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $i = 2;

        while (
        BlogPost::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()
        ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
    private function categoryOptions($categories): array
    {
        // $categories: collection (id, name, parent_id)
        $byParent = [];
        foreach ($categories as $c) {
            $pid = (int) ($c->parent_id ?? 0);
            $byParent[$pid][] = $c;
        }

        $out = [];

        $walk = function ($parentId, $depth) use (&$walk, &$out, $byParent) {
            $list = $byParent[(int)$parentId] ?? [];
            foreach ($list as $c) {
                $prefix = str_repeat('— ', $depth);
                $out[] = [
                    'id' => (int) $c->id,
                    'label' => $prefix . $c->name,
                ];
                $walk((int)$c->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $out;
    }
    private function syncFeaturedMedia(BlogPost $post, ?int $mediaId): void
    {
        DB::table('mediables')
            ->where('mediable_type', BlogPost::class)
            ->where('mediable_id', $post->id)
            ->where('collection', 'featured')
            ->delete();

        if (!$mediaId) {
            return;
        }

        DB::table('mediables')->insert([
            'media_id' => $mediaId,
            'mediable_type' => BlogPost::class,
            'mediable_id' => $post->id,
            'collection' => 'featured',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
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
                'message' => 'Slug boş olamaz.',
            ]);
        }

        $exists = BlogPost::query()
            ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists();

        return response()->json([
            'ok' => true,
            'available' => !$exists,
            'message' => $exists ? 'Bu slug zaten kullanılıyor.' : 'Slug uygun.',
        ]);
    }
}
