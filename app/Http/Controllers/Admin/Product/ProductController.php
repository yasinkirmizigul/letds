<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ProductStoreRequest;
use App\Http\Requests\Admin\Product\ProductUpdateRequest;
use App\Models\Admin\Category;
use App\Models\Admin\Product\Product;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->string('mode', 'active')->toString(); // active|trash
        $isTrash = $mode === 'trash';

        $q = $request->string('q')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $query = $isTrash ? Product::onlyTrashed() : Product::query();

        $items = $query
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.products.index', [
            'mode' => $isTrash ? 'trash' : 'active',
            'products' => $items,
            'q' => $q,
            'perPage' => $perPage,
            'pageTitle' => 'Ürünler',
        ]);
    }

    public function trash(Request $request)
    {
        $request->merge(['mode' => 'trash']);
        return $this->index($request);
    }

    /**
     * Opsiyonel JSON list endpoint (mode destekli)
     * /admin/products/list?mode=trash&q=...&perpage=25&page=1
     */
    public function list(Request $request): JsonResponse
    {
        $mode = $request->string('mode', 'active')->toString();
        $q = $request->string('q')->toString();
        $perPage = max(1, min(100, (int) $request->input('perpage', 25)));

        $query = $mode === 'trash'
            ? Product::onlyTrashed()->latest('id')
            : Product::query()->latest('id');

        $items = $query
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
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

    public function create()
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $categoryOptions = $this->categoryOptions($categories);

        return view('admin.pages.products.create', [
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => [],
            'statusOptions' => Product::statusOptionsSorted(),
            'pageTitle' => 'Ürün Ekle',
        ]);
    }

    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();

        // Status (create/edit formdan gelecek)
        $status = (string)($request->input('status') ?? Product::STATUS_APPOINTMENT_PENDING);

        $data = array_merge($data, [
            'status' => $status,
        ]);

        // Slug
        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug);

        $featuredMediaId = $data['featured_media_id'] ?? null;
        unset($data['featured_media_id']);

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        // Featured (max 5) - DB seviyesinde garanti
        $makeFeatured = (bool) $request->boolean('is_featured');
        if ($makeFeatured) {
            $count = Product::where('is_featured', true)->lockForUpdate()->count();
            if ($count >= 5) {
                return back()->withErrors([
                    'is_featured' => 'En fazla 5 ürün aynı anda anasayfada görünebilir.'
                ])->withInput();
            }
        }

        // status whitelist (model kaynağıyla aynı)
        if (!array_key_exists($status, Product::STATUS_OPTIONS)) {
            return back()->withErrors(['status' => 'Geçersiz durum seçimi.'])->withInput();
        }

        $product = DB::transaction(function () use ($data, $categoryIds, $featuredMediaId, $makeFeatured) {
            if ($makeFeatured) {
                // aynı anda 5 sınırını aşmasın (concurrency-safe)
                $count = Product::where('is_featured', true)->lockForUpdate()->count();
                if ($count >= 5) {
                    return null; // dışarıda handle edeceğiz
                }
            }

            $product = Product::create($data);

            // categories
            if (method_exists($product, 'categories')) {
                $product->categories()->sync($categoryIds);
            }

            // featured media
            if ($featuredMediaId) {
                $this->syncFeaturedMedia($product, (int) $featuredMediaId);
            }

            // featured flag
            if ($makeFeatured) {
                $product->is_featured = true;
                $product->featured_at = now();
            } else {
                $product->is_featured = false;
                $product->featured_at = null;
            }
            $product->save();

            return $product;
        });

        if (!$product) {
            return back()->withErrors([
                'is_featured' => 'En fazla 5 ürün anasayfada görünebilir.'
            ])->withInput();
        }

        AuditEvent::log('products.create', [
            'product_id' => (int) $product->id,
            'title' => (string) $product->title,
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Ürün oluşturuldu.');
    }

    public function edit(Product $product)
    {
        try {
            $product->load(['categories' => fn ($q) => $q->withTrashed()]);
        } catch (\Throwable $e) {
            $product->load('categories');
        }

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $categoryOptions = $this->categoryOptions($categories);

        $selectedCategoryIds = $product->categories
            ? $product->categories->pluck('id')->map(fn ($v) => (int) $v)->values()->all()
            : [];

        $featuredMediaId = (int) (DB::table('mediables')
            ->where('mediable_type', Product::class)
            ->where('mediable_id', $product->id)
            ->where('collection', 'featured')
            ->orderBy('order')
            ->value('media_id') ?? 0);

        return view('admin.pages.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'selectedCategoryIds' => $selectedCategoryIds,
            'featuredMediaId' => $featuredMediaId ?: null,
            'statusOptions' => Product::statusOptionsSorted(),
            'pageTitle' => 'Ürün Düzenle',
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();

        // Status
        $status = (string)($request->input('status') ?? ($product->status ?? Product::STATUS_APPOINTMENT_PENDING));
        if (!array_key_exists($status, Product::STATUS_OPTIONS)) {
            return back()->withErrors(['status' => 'Geçersiz durum seçimi.'])->withInput();
        }
        $data['status'] = $status;

        // Slug
        $slug = $data['slug'] ?: Str::slug($data['title']);
        $data['slug'] = $this->uniqueSlug($slug, $product->id);

        $featuredMediaId = $data['featured_media_id'] ?? null;
        unset($data['featured_media_id']);

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $oldStatus = (string) ($product->status ?? Product::STATUS_APPOINTMENT_PENDING);

        // Featured (max 5) - concurrency-safe
        $makeFeatured = (bool) $request->boolean('is_featured');

        if ($makeFeatured && !(bool) $product->is_featured) {
            $count = Product::where('is_featured', true)->lockForUpdate()->count();
            if ($count >= 5) {
                return back()->withErrors([
                    'is_featured' => 'En fazla 5 ürün aynı anda anasayfada görünebilir.'
                ])->withInput();
            }
        }

        $res = DB::transaction(function () use ($product, $data, $categoryIds, $featuredMediaId, $makeFeatured) {
            $product->refresh();

            if ($makeFeatured && !$product->is_featured) {
                $count = Product::where('is_featured', true)->lockForUpdate()->count();
                if ($count >= 5) {
                    return null;
                }
            }

            $product->update($data);

            if (method_exists($product, 'categories')) {
                $product->categories()->sync($categoryIds);
            }

            $this->syncFeaturedMedia($product, $featuredMediaId ? (int) $featuredMediaId : null);

            if ($makeFeatured) {
                $product->is_featured = true;
                $product->featured_at = $product->featured_at ?: now();
            } else {
                $product->is_featured = false;
                $product->featured_at = null;
            }
            $product->save();

            return $product;
        });

        if (!$res) {
            return back()->withErrors([
                'is_featured' => 'En fazla 5 ürün anasayfada görünebilir.'
            ])->withInput();
        }

        AuditEvent::log('products.update', [
            'product_id' => (int) $product->id,
        ]);

        if (($data['status'] ?? null) && $oldStatus !== $data['status']) {
            AuditEvent::log('products.workflow.change', [
                'product_id' => (int) $product->id,
                'from' => $oldStatus,
                'to'   => (string) $data['status'],
            ]);
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        AuditEvent::log('products.delete', [
            'product_id' => (int) $product->id,
        ]);

        return response()->json(['ok' => true]);
    }

    public function restore(int $id): JsonResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        AuditEvent::log('products.restore', [
            'product_id' => (int) $product->id,
        ]);

        return response()->json(['ok' => true, 'data' => ['restored' => true]]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($product) {
            // categories pivot temizliği
            if (method_exists($product, 'categories')) {
                $product->categories()->detach();
            }

            // featured mediable temizliği
            DB::table('mediables')
                ->where('mediable_type', Product::class)
                ->where('mediable_id', $product->id)
                ->delete();

            // galleryables temizliği (varsa)
            DB::table('galleryables')
                ->where('galleryable_type', Product::class)
                ->where('galleryable_id', $product->id)
                ->delete();

            $product->forceDelete();
        });

        AuditEvent::log('products.force_delete', [
            'product_id' => (int) $product->id,
        ]);

        return response()->json(['ok' => true, 'data' => ['force_deleted' => true]]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = Product::query()->whereIn('id', $ids)->delete();

        AuditEvent::log('products.bulk.delete', [
            'ids' => $ids,
            'deleted' => (int) $count,
        ]);

        return response()->json(['ok' => true, 'data' => ['deleted' => $count]]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $count = Product::onlyTrashed()->whereIn('id', $ids)->restore();

        AuditEvent::log('products.bulk.restore', [
            'ids' => $ids,
            'restored' => (int) $count,
        ]);

        return response()->json(['ok' => true, 'data' => ['restored' => $count]]);
    }

    public function bulkForceDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return response()->json(['ok' => false, 'error' => ['message' => 'Seçili kayıt yok.']], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $products = Product::withTrashed()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($products) {
            foreach ($products as $p) {
                if (method_exists($p, 'categories')) {
                    $p->categories()->detach();
                }

                DB::table('mediables')
                    ->where('mediable_type', Product::class)
                    ->where('mediable_id', $p->id)
                    ->delete();

                DB::table('galleryables')
                    ->where('galleryable_type', Product::class)
                    ->where('galleryable_id', $p->id)
                    ->delete();

                $p->forceDelete();
            }
        });

        AuditEvent::log('products.bulk.force_delete', [
            'ids' => $ids,
            'force_deleted' => (int) $products->count(),
        ]);

        return response()->json(['ok' => true, 'data' => ['force_deleted' => $products->count()]]);
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $i = 2;

        while (
            Product::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
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
        $byParent = [];
        foreach ($categories as $c) {
            $pid = (int) ($c->parent_id ?? 0);
            $byParent[$pid][] = $c;
        }

        $out = [];
        $walk = function ($parentId, $depth) use (&$walk, &$out, $byParent) {
            $list = $byParent[(int) $parentId] ?? [];
            foreach ($list as $c) {
                $prefix = str_repeat('— ', $depth);
                $out[] = [
                    'id' => (int) $c->id,
                    'label' => $prefix . $c->name,
                ];
                $walk((int) $c->id, $depth + 1);
            }
        };

        $walk(0, 0);
        return $out;
    }

    private function syncFeaturedMedia(Product $product, ?int $mediaId): void
    {
        // mevcut featured temizle
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

        $exists = Product::query()
            ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists();

        return response()->json([
            'ok' => true,
            'available' => !$exists,
            'message' => $exists ? 'Bu slug zaten kullanılıyor.' : 'Slug uygun.',
        ]);
    }

    public function updateStatus(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(Product::STATUS_OPTIONS))],
        ]);

        $from = (string)($product->status ?? Product::STATUS_APPOINTMENT_PENDING);
        $to   = (string)$data['status'];

        if ($from !== $to) {
            $product->status = $to;
            $product->save();

            AuditEvent::log('products.workflow.change', [
                'product_id' => (int)$product->id,
                'from' => $from,
                'to'   => $to,
            ]);
        }

        return response()->json([
            'ok' => true,
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
        $make = (bool)$request->boolean('is_featured');

        $res = DB::transaction(function () use ($product, $make) {
            $product->refresh();

            if ($make) {
                if (!$product->is_featured) {
                    $count = Product::where('is_featured', true)->lockForUpdate()->count();
                    if ($count >= 5) {
                        return response()->json([
                            'ok' => false,
                            'error' => ['message' => 'En fazla 5 ürün anasayfada görünebilir.']
                        ], 422);
                    }
                }

                $product->is_featured = true;
                $product->featured_at = now();
            } else {
                $product->is_featured = false;
                $product->featured_at = null;
            }

            $product->save();

            return response()->json([
                'ok' => true,
                'data' => [
                    'id' => $product->id,
                    'is_featured' => (bool)$product->is_featured,
                ],
            ]);
        });

        return $res;
    }
}
