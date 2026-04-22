<?php

namespace App\Http\Controllers\Admin\BlogPost;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\BlogStoreRequest;
use App\Http\Requests\Admin\Blog\BlogUpdateRequest;
use App\Models\Admin\BlogPost\BlogPost;
use App\Models\Admin\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlogPostController extends Controller
{
    public function index(Request $request): View
    {
        $mode = $request->string('mode', 'active')->toString();
        $isTrash = $mode === 'trash';

        $q = trim($request->string('q')->toString());
        $status = $request->string('status', 'all')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $selectedCategoryIds = collect($request->input('category_ids', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $query = ($isTrash ? BlogPost::onlyTrashed() : BlogPost::query())
            ->with([
                'author:id,name',
                'categories:id,name',
                'featuredMedia',
            ])
            ->search($q)
            ->when(!empty($selectedCategoryIds), function ($builder) use ($selectedCategoryIds) {
                $builder->whereHas('categories', function ($categoriesQuery) use ($selectedCategoryIds) {
                    $categoriesQuery->whereIn('categories.id', $selectedCategoryIds);
                });
            })
            ->when($status === 'published', fn ($builder) => $builder->published())
            ->when($status === 'draft', fn ($builder) => $builder->draft())
            ->when($status === 'featured', fn ($builder) => $builder->featured())
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at');

        $posts = $query->get();

        return view('admin.pages.blog.index', [
            'mode' => $isTrash ? 'trash' : 'active',
            'posts' => $posts,
            'q' => $q,
            'status' => $status,
            'perPage' => $perPage,
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => $selectedCategoryIds,
            'stats' => [
                'all' => BlogPost::query()->count(),
                'published' => BlogPost::query()->published()->count(),
                'draft' => BlogPost::query()->draft()->count(),
                'featured' => BlogPost::query()->featured()->count(),
                'trash' => BlogPost::onlyTrashed()->count(),
            ],
            'pageTitle' => 'Blog',
        ]);
    }

    public function trash(Request $request): View
    {
        $request->merge(['mode' => 'trash']);

        return $this->index($request);
    }

    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();
        $q = trim($request->string('q')->toString());
        $status = $request->string('status', 'all')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $query = ($mode === 'trash' ? BlogPost::onlyTrashed() : BlogPost::query())
            ->with(['categories:id,name', 'author:id,name'])
            ->search($q)
            ->when($status === 'published', fn ($builder) => $builder->published())
            ->when($status === 'draft', fn ($builder) => $builder->draft())
            ->when($status === 'featured', fn ($builder) => $builder->featured())
            ->latest('id');

        $items = $query->paginate($perPage);

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

    public function create(): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('admin.pages.blog.create', [
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => [],
            'pageTitle' => 'Yazı Ekle',
        ]);
    }

    public function store(BlogStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $post = DB::transaction(function () use ($validated, $request) {
            $post = BlogPost::create($this->buildPersistenceData($validated, $request));
            $this->syncCategories($post, $validated['category_ids'] ?? []);

            return $post;
        });

        $this->syncFeaturedAsset(
            $post,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog yazısı oluşturuldu.');
    }

    public function edit(BlogPost $blogPost): View
    {
        $blogPost->load([
            'categories:id,name,parent_id',
            'featuredMedia',
            'author:id,name',
            'editör:id,name',
        ]);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('admin.pages.blog.edit', [
            'blogPost' => $blogPost,
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => $blogPost->categories
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all(),
            'pageTitle' => 'Yazı Düzenle',
        ]);
    }

    public function update(BlogUpdateRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, &$blogPost) {
            $blogPost = BlogPost::query()->lockForUpdate()->findOrFail($blogPost->id);
            $blogPost->update($this->buildPersistenceData($validated, $request, $blogPost));
            $this->syncCategories($blogPost, $validated['category_ids'] ?? []);
        });

        $this->syncFeaturedAsset(
            $blogPost,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog yazısı güncellendi.');
    }

    public function destroy(Request $request, BlogPost $blogPost)
    {
        $blogPost->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Blog yazısı çöp kutusuna taşındı.',
            ]);
        }

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog yazısı çöp kutusuna taşındı.');
    }

    public function restore(int $id): JsonResponse
    {
        $post = BlogPost::onlyTrashed()->findOrFail($id);
        $post->restore();

        return response()->json([
            'ok' => true,
            'message' => 'Blog yazısı geri yüklendi.',
            'data' => ['restored' => true],
        ]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $post = BlogPost::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($post) {
            $this->syncCategories($post, []);
            $this->deleteLegacyFeaturedImage($post);
            $this->syncFeaturedMedia($post, null);
            $post->forceDelete();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Blog yazısı kalıcı olarak silindi.',
            'data' => ['force_deleted' => true],
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $count = BlogPost::query()->whereIn('id', $ids)->delete();

        return response()->json([
            'ok' => true,
            'message' => $count . ' blog yazısı çöp kutusuna taşındı.',
            'data' => ['deleted' => $count],
        ]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $count = BlogPost::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json([
            'ok' => true,
            'message' => $count . ' blog yazısı geri yüklendi.',
            'data' => ['restored' => $count],
        ]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $posts = BlogPost::withTrashed()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($posts) {
            foreach ($posts as $post) {
                $this->syncCategories($post, []);
                $this->deleteLegacyFeaturedImage($post);
                $this->syncFeaturedMedia($post, null);
                $post->forceDelete();
            }
        });

        return response()->json([
            'ok' => true,
            'message' => $posts->count() . ' blog yazısı kalıcı olarak silindi.',
            'data' => ['force_deleted' => $posts->count()],
        ]);
    }

    public function togglePublish(Request $request, BlogPost $blogPost): JsonResponse
    {
        $payload = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);

        return DB::transaction(function () use ($blogPost, $payload) {
            $blogPost = BlogPost::query()->lockForUpdate()->findOrFail($blogPost->id);
            $want = (bool) $payload['is_published'];

            $blogPost->is_published = $want;
            $blogPost->published_at = $want ? ($blogPost->published_at ?? now()) : null;
            $blogPost->updated_by = auth()->id();
            $blogPost->save();

            $badgeHtml = $blogPost->is_published
                ? '<span class="kt-badge kt-badge-sm kt-badge-success">Yayında</span>'
                : '<span class="kt-badge kt-badge-sm kt-badge-light">Taslak</span>';

            return response()->json([
                'ok' => true,
                'message' => $blogPost->is_published ? 'Yayın durumu güncellendi.' : 'Yazı taslak durumuna alındı.',
                'is_published' => (bool) $blogPost->is_published,
                'published_at' => $blogPost->published_at ? $blogPost->published_at->format('d.m.Y H:i') : null,
                'badge_html' => $badgeHtml,
            ]);
        });
    }

    public function toggleFeatured(Request $request, BlogPost $blogPost): JsonResponse
    {
        $payload = $request->validate([
            'is_featured' => ['required', 'boolean'],
        ]);

        return DB::transaction(function () use ($blogPost, $payload) {
            $blogPost = BlogPost::query()->lockForUpdate()->findOrFail($blogPost->id);
            $want = (bool) $payload['is_featured'];

            if ($want) {
                $this->guardFeaturedLimit($blogPost->id);
                $blogPost->is_featured = true;
                $blogPost->featured_at = $blogPost->featured_at ?? now();
            } else {
                $blogPost->is_featured = false;
                $blogPost->featured_at = null;
            }

            $blogPost->updated_by = auth()->id();
            $blogPost->save();

            $badgeHtml = $blogPost->is_featured
                ? '<span class="kt-badge kt-badge-sm kt-badge-light-success">Anasayfada</span>'
                : '<span class="kt-badge kt-badge-sm kt-badge-light text-muted-foreground">Kapalı</span>';

            return response()->json([
                'ok' => true,
                'message' => $blogPost->is_featured ? 'Yazı anasayfada gösterilecek.' : 'Yazı anasayfadan kaldırıldı.',
                'is_featured' => (bool) $blogPost->is_featured,
                'featured_at' => $blogPost->featured_at ? $blogPost->featured_at->format('d.m.Y H:i') : null,
                'badge_html' => $badgeHtml,
            ]);
        });
    }

    public function checkSlug(Request $request): JsonResponse
    {
        $rawSlug = trim((string) $request->query('slug', ''));
        $ignoreId = $request->integer('ignore');
        $normalizedSlug = Str::slug($rawSlug);

        if ($normalizedSlug === '') {
            return response()->json([
                'ok' => false,
                'available' => false,
                'message' => 'Slug boş olamaz.',
            ]);
        }

        $suggested = $this->uniqueSlug($normalizedSlug, $ignoreId);
        $isAvailable = $suggested === $normalizedSlug;

        return response()->json([
            'ok' => true,
            'available' => $isAvailable,
            'normalized' => $normalizedSlug,
            'suggested' => $suggested,
            'message' => $isAvailable ? 'Slug uygun.' : 'Bu slug kullanılıyor. Onerilen slug hazırlandı.',
        ]);
    }

    private function buildPersistenceData(array $validated, Request $request, ?BlogPost $blogPost = null): array
    {
        $slugSource = $validated['slug'] ?: $validated['title'];

        $data = [
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($slugSource, $blogPost?->id),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'published_at' => $this->resolvePublishedAt((bool) ($validated['is_published'] ?? false), $blogPost),
            'featured_at' => $this->resolveFeaturedAt((bool) ($validated['is_featured'] ?? false), $blogPost),
            'updated_by' => $request->user()?->id,
        ];

        if (!$blogPost) {
            $data['created_by'] = $request->user()?->id;
        }

        if ($data['is_featured']) {
            $this->guardFeaturedLimit($blogPost?->id);
        }

        return $data;
    }

    private function syncCategories(BlogPost $post, array $categoryIds): void
    {
        if (!method_exists($post, 'categories')) {
            return;
        }

        $ids = collect($categoryIds)
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $post->categories()->sync($ids);
    }

    private function syncFeaturedAsset(
        BlogPost $post,
        Request $request,
        ?int $featuredMediaId,
        bool $clearFeaturedImage
    ): void {
        if ($request->hasFile('featured_image')) {
            $this->deleteLegacyFeaturedImage($post);

            $path = $request->file('featured_image')->store('blog/featured', 'public');
            $post->forceFill(['featured_image_path' => $path])->save();

            $this->syncFeaturedMedia($post, null);

            return;
        }

        if ($clearFeaturedImage && !$featuredMediaId) {
            $this->deleteLegacyFeaturedImage($post);
            $this->syncFeaturedMedia($post, null);

            return;
        }

        $this->syncFeaturedMedia($post, $featuredMediaId);
    }

    private function deleteLegacyFeaturedImage(BlogPost $post): void
    {
        if (!$post->featured_image_path) {
            return;
        }

        Storage::disk('public')->delete($post->featured_image_path);
        $post->forceFill(['featured_image_path' => null])->save();
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

    private function resolvePublishedAt(bool $isPublished, ?BlogPost $blogPost = null)
    {
        if (!$isPublished) {
            return null;
        }

        return $blogPost?->published_at ?? now();
    }

    private function resolveFeaturedAt(bool $isFeatured, ?BlogPost $blogPost = null)
    {
        if (!$isFeatured) {
            return null;
        }

        return $blogPost?->featured_at ?? now();
    }

    private function guardFeaturedLimit(?int $exceptId = null): void
    {
        $query = BlogPost::query()
            ->where('is_featured', true)
            ->lockForUpdate();

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->count() >= 5) {
            throw ValidationException::withMessages([
                'is_featured' => 'Aynı anda en fazla 5 blog anasayfada gösterilebilir.',
            ]);
        }
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $suffix = 2;

        while (
            BlogPost::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function validatedBulkIds(Request $request): array
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            throw ValidationException::withMessages([
                'ids' => 'Seçili kayıt yok.',
            ]);
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function categoryOptions($categories): array
    {
        $byParent = [];

        foreach ($categories as $category) {
            $parentId = (int) ($category->parent_id ?? 0);
            $byParent[$parentId][] = $category;
        }

        $options = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$options, $byParent) {
            foreach ($byParent[$parentId] ?? [] as $category) {
                $options[] = [
                    'id' => (int) $category->id,
                    'label' => str_repeat('— ', $depth) . $category->name,
                ];

                $walk((int) $category->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $options;
    }
}
