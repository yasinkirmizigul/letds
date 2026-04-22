<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ProductStoreRequest;
use App\Http\Requests\Admin\Product\ProductUpdateRequest;
use App\Models\Admin\Category;
use App\Models\Admin\Product\Product;
use App\Support\Audit\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
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

        $products = ($isTrash ? Product::onlyTrashed() : Product::query())
            ->with(['categories:id,name', 'featuredMedia'])
            ->search($q)
            ->inStatus($status)
            ->when(!empty($selectedCategoryIds), function ($builder) use ($selectedCategoryIds) {
                $builder->whereHas('categories', function ($categoryQuery) use ($selectedCategoryIds) {
                    $categoryQuery->whereIn('categories.id', $selectedCategoryIds);
                });
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.pages.products.index', [
            'mode' => $isTrash ? 'trash' : 'active',
            'products' => $products,
            'q' => $q,
            'status' => $status,
            'perPage' => $perPage,
            'statusOptions' => Product::statusOptionsSorted(),
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => $selectedCategoryIds,
            'stats' => [
                'all' => Product::query()->count(),
                'active' => Product::query()->where('status', Product::STATUS_ACTIVE)->count(),
                'featured' => Product::query()->featured()->count(),
                'low_stock' => Product::query()->lowStock()->count(),
                'trash' => Product::onlyTrashed()->count(),
            ],
            'pageTitle' => 'Urunler',
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

        $selectedCategoryIds = collect($request->input('category_ids', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $items = ($mode === 'trash' ? Product::onlyTrashed() : Product::query())
            ->with(['categories:id,name', 'featuredMedia'])
            ->search($q)
            ->inStatus($status)
            ->when(!empty($selectedCategoryIds), function ($builder) use ($selectedCategoryIds) {
                $builder->whereHas('categories', function ($categoryQuery) use ($selectedCategoryIds) {
                    $categoryQuery->whereIn('categories.id', $selectedCategoryIds);
                });
            })
            ->latest('id')
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

    public function create(): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('admin.pages.products.create', [
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => [],
            'statusOptions' => Product::statusOptionsSorted(),
            'pageTitle' => 'Urun Ekle',
        ]);
    }

    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create($this->buildPersistenceData($validated));
            $this->syncCategories($product, $validated['category_ids'] ?? []);

            return $product;
        });

        $this->syncFeaturedAsset(
            $product,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        AuditEvent::log('products.create', [
            'product_id' => (int) $product->id,
            'title' => (string) $product->title,
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Urun olusturuldu.');
    }

    public function edit(Product $product): View
    {
        $product->load(['categories:id,name,parent_id', 'featuredMedia']);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('admin.pages.products.edit', [
            'product' => $product,
            'categoryOptions' => $this->categoryOptions($categories),
            'selectedCategoryIds' => $product->categories
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all(),
            'featuredMediaId' => $product->featuredMediaOne()?->id,
            'statusOptions' => Product::statusOptionsSorted(),
            'pageTitle' => 'Urun Duzenle',
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $oldStatus = (string) ($product->status ?? Product::STATUS_APPOINTMENT_PENDING);

        DB::transaction(function () use ($validated, &$product) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $product->update($this->buildPersistenceData($validated, $product));
            $this->syncCategories($product, $validated['category_ids'] ?? []);
        });

        $this->syncFeaturedAsset(
            $product,
            $request,
            isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            (bool) ($validated['clear_featured_image'] ?? false)
        );

        AuditEvent::log('products.update', [
            'product_id' => (int) $product->id,
        ]);

        if ($oldStatus !== ($validated['status'] ?? $oldStatus)) {
            AuditEvent::log('products.workflow.change', [
                'product_id' => (int) $product->id,
                'from' => $oldStatus,
                'to' => (string) $validated['status'],
            ]);
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Urun guncellendi.');
    }

    public function destroy(Request $request, Product $product)
    {
        $product->delete();

        AuditEvent::log('products.delete', [
            'product_id' => (int) $product->id,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Urun cop kutusuna tasindi.',
            ]);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Urun cop kutusuna tasindi.');
    }

    public function restore(int $id): JsonResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        AuditEvent::log('products.restore', [
            'product_id' => (int) $product->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Urun geri yuklendi.',
            'data' => ['restored' => true],
        ]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($product) {
            $this->syncCategories($product, []);
            $this->deleteLegacyFeaturedImage($product);
            $this->syncFeaturedMedia($product, null);

            DB::table('galleryables')
                ->where('galleryable_type', Product::class)
                ->where('galleryable_id', $product->id)
                ->delete();

            $product->forceDelete();
        });

        AuditEvent::log('products.force_delete', [
            'product_id' => (int) $product->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Urun kalici olarak silindi.',
            'data' => ['force_deleted' => true],
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $count = Product::query()->whereIn('id', $ids)->delete();

        AuditEvent::log('products.bulk.delete', [
            'ids' => $ids,
            'deleted' => (int) $count,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $count . ' urun cop kutusuna tasindi.',
            'data' => ['deleted' => $count],
        ]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $count = Product::onlyTrashed()->whereIn('id', $ids)->restore();

        AuditEvent::log('products.bulk.restore', [
            'ids' => $ids,
            'restored' => (int) $count,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $count . ' urun geri yuklendi.',
            'data' => ['restored' => $count],
        ]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $this->validatedBulkIds($request);
        $products = Product::withTrashed()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($products) {
            foreach ($products as $product) {
                $this->syncCategories($product, []);
                $this->deleteLegacyFeaturedImage($product);
                $this->syncFeaturedMedia($product, null);

                DB::table('galleryables')
                    ->where('galleryable_type', Product::class)
                    ->where('galleryable_id', $product->id)
                    ->delete();

                $product->forceDelete();
            }
        });

        AuditEvent::log('products.bulk.force_delete', [
            'ids' => $ids,
            'force_deleted' => (int) $products->count(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => $products->count() . ' urun kalici olarak silindi.',
            'data' => ['force_deleted' => $products->count()],
        ]);
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
                'message' => 'Slug bos olamaz.',
            ]);
        }

        $suggested = $this->uniqueSlug($normalizedSlug, $ignoreId);
        $isAvailable = $suggested === $normalizedSlug;

        return response()->json([
            'ok' => true,
            'available' => $isAvailable,
            'normalized' => $normalizedSlug,
            'suggested' => $suggested,
            'message' => $isAvailable ? 'Slug uygun.' : 'Bu slug kullaniliyor. Onerilen slug hazirlandi.',
        ]);
    }

    public function updateStatus(Request $request, Product $product): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(Product::STATUS_OPTIONS))],
        ]);

        $from = (string) ($product->status ?? Product::STATUS_APPOINTMENT_PENDING);
        $to = (string) $payload['status'];

        if ($from !== $to) {
            $product->status = $to;
            $product->save();

            AuditEvent::log('products.workflow.change', [
                'product_id' => (int) $product->id,
                'from' => $from,
                'to' => $to,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Urun durumu guncellendi.',
            'data' => [
                'id' => $product->id,
                'status' => $product->status,
                'status_label' => Product::statusLabel($product->status),
                'status_badge' => Product::statusBadgeClass($product->status),
            ],
        ]);
    }

    public function toggleFeatured(Request $request, Product $product): JsonResponse
    {
        $payload = $request->validate([
            'is_featured' => ['required', 'boolean'],
        ]);

        return DB::transaction(function () use ($product, $payload) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $want = (bool) $payload['is_featured'];
            $wasFeatured = (bool) $product->is_featured;

            if ($want) {
                $this->guardFeaturedLimit($product->id);
                $product->is_featured = true;
                $product->featured_at = $product->featured_at ?? now();
            } else {
                $product->is_featured = false;
                $product->featured_at = null;
            }

            $product->save();

            if ($wasFeatured !== (bool) $product->is_featured) {
                AuditEvent::log('products.featured.toggle', [
                    'product_id' => (int) $product->id,
                    'is_featured' => (bool) $product->is_featured,
                ]);
            }

            return response()->json([
                'ok' => true,
                'message' => $product->is_featured ? 'Urun anasayfaya alindi.' : 'Urun anasayfadan kaldirildi.',
                'data' => [
                    'id' => $product->id,
                    'is_featured' => (bool) $product->is_featured,
                    'featured_at' => $product->featured_at?->format('d.m.Y H:i'),
                ],
            ]);
        });
    }

    private function buildPersistenceData(array $validated, ?Product $product = null): array
    {
        $slugSource = $validated['slug'] ?: $validated['title'];

        $data = [
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($slugSource, $product?->id),
            'content' => $validated['content'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'price' => $validated['price'] ?? null,
            'stock' => $validated['stock'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'sale_price' => $validated['sale_price'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'vat_rate' => $validated['vat_rate'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'width' => $validated['width'] ?? null,
            'height' => $validated['height'] ?? null,
            'length' => $validated['length'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 0,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'status' => $validated['status'] ?? Product::STATUS_APPOINTMENT_PENDING,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'featured_at' => $this->resolveFeaturedAt((bool) ($validated['is_featured'] ?? false), $product),
            'appointment_id' => $validated['appointment_id'] ?? null,
        ];

        if ($data['is_featured']) {
            $this->guardFeaturedLimit($product?->id);
        }

        return $data;
    }

    private function syncCategories(Product $product, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $product->categories()->sync($ids);
    }

    private function syncFeaturedAsset(
        Product $product,
        Request $request,
        ?int $featuredMediaId,
        bool $clearFeaturedImage
    ): void {
        if ($request->hasFile('featured_image')) {
            $this->deleteLegacyFeaturedImage($product);

            $path = $request->file('featured_image')->store('products/featured', 'public');
            $product->forceFill(['featured_image_path' => $path])->save();

            $this->syncFeaturedMedia($product, null);

            AuditEvent::log('products.featured.upload', [
                'product_id' => (int) $product->id,
                'path' => $path,
            ]);

            return;
        }

        if ($clearFeaturedImage && !$featuredMediaId) {
            $this->deleteLegacyFeaturedImage($product);
            $this->syncFeaturedMedia($product, null);

            return;
        }

        $this->syncFeaturedMedia($product, $featuredMediaId);
    }

    private function deleteLegacyFeaturedImage(Product $product): void
    {
        if (!$product->featured_image_path) {
            return;
        }

        Storage::disk('public')->delete($product->featured_image_path);
        $product->forceFill(['featured_image_path' => null])->save();
    }

    private function syncFeaturedMedia(Product $product, ?int $mediaId): void
    {
        DB::table('mediables')
            ->where('mediable_type', Product::class)
            ->where('mediable_id', $product->id)
            ->where('collection', 'featured')
            ->delete();

        if (!$mediaId) {
            AuditEvent::log('products.featured.detach', [
                'product_id' => (int) $product->id,
            ]);

            return;
        }

        DB::table('mediables')->insert([
            'media_id' => $mediaId,
            'mediable_type' => Product::class,
            'mediable_id' => $product->id,
            'collection' => 'featured',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditEvent::log('products.featured.attach', [
            'product_id' => (int) $product->id,
            'media_id' => (int) $mediaId,
        ]);
    }

    private function resolveFeaturedAt(bool $isFeatured, ?Product $product = null)
    {
        if (!$isFeatured) {
            return null;
        }

        return $product?->featured_at ?? now();
    }

    private function guardFeaturedLimit(?int $exceptId = null): void
    {
        $query = Product::query()
            ->where('is_featured', true)
            ->lockForUpdate();

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->count() >= 5) {
            throw ValidationException::withMessages([
                'is_featured' => 'En fazla 5 urun ayni anda anasayfada gosterilebilir.',
            ]);
        }
    }

    private function validatedBulkIds(Request $request): array
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            throw ValidationException::withMessages([
                'ids' => 'Secili kayit yok.',
            ]);
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'product';
        $candidate = $base;
        $suffix = 2;

        while (
            Product::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
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
                    'label' => str_repeat('-- ', $depth) . $category->name,
                ];

                $walk((int) $category->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $options;
    }
}
