<?php

namespace App\Http\Controllers\Admin\BlogPost;

use App\Http\Controllers\Controller;
use App\Models\Admin\BlogPost\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $query = $isTrash
            ? BlogPost::onlyTrashed()
            : BlogPost::query();

        $posts = $query
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
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
        return view('admin.pages.blog.create');
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
        ]);

        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug);

        $post = BlogPost::create($data);

        if (method_exists($post, 'categories')) {
            $post->categories()->sync($data['category_ids'] ?? []);
        }

        return redirect()->route('admin.blog.index')->with('success', 'Blog yazısı oluşturuldu.');
    }

    public function edit(BlogPost $blogPost)
    {
        return view('admin.pages.blog.edit', ['post' => $blogPost]);
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
        ]);

        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug, $blogPost->id);

        $blogPost->update($data);

        if (method_exists($blogPost, 'categories')) {
            $blogPost->categories()->sync($data['category_ids'] ?? []);
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
}
