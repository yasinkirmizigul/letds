<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Controller;
use App\Models\Admin\Gallery\Gallery;
use App\Models\Admin\Product\Product;
use App\Support\Audit\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductGalleryController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $rows = DB::table('galleryables')
            ->where('galleryable_type', Product::class)
            ->where('galleryable_id', $product->id)
            ->orderBy('slot')
            ->orderBy('sort_order')
            ->get(['id', 'gallery_id', 'slot', 'sort_order']);

        $galleries = Gallery::query()
            ->whereIn('id', $rows->pluck('gallery_id')->unique()->values()->all())
            ->get(['id', 'name', 'slug', 'description'])
            ->keyBy('id');

        $data = $rows->map(function ($row) use ($galleries) {
            $gallery = $galleries->get($row->gallery_id);

            return [
                'pivot_id' => (int) $row->id,
                'gallery_id' => (int) $row->gallery_id,
                'slot' => (string) $row->slot,
                'sort_order' => (int) $row->sort_order,
                'gallery' => $gallery ? [
                    'id' => (int) $gallery->id,
                    'name' => (string) $gallery->name,
                    'slug' => (string) $gallery->slug,
                    'description' => $gallery->description,
                ] : null,
            ];
        })->values();

        return response()->json(['ok' => true, 'data' => $data]);
    }

    public function attach(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required', 'integer', 'exists:galleries,id'],
            'slot' => ['nullable', 'string', 'max:30'],
        ]);

        $slot = $this->normalizeSlot($data['slot'] ?? null);

        $existing = DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', Product::class)
            ->where('galleryable_id', $product->id)
            ->first(['id', 'slot']);

        if ($existing) {
            if ($existing->slot !== $slot) {
                DB::table('galleryables')->where('id', $existing->id)->update([
                    'slot' => $slot,
                    'sort_order' => $this->nextSortOrder($product, $slot),
                    'updated_at' => now(),
                ]);
            }

            AuditEvent::log('products.gallery.attach', [
                'product_id' => (int) $product->id,
                'gallery_id' => (int) $data['gallery_id'],
                'slot' => $slot,
                'already_attached' => true,
            ]);

            return response()->json(['ok' => true, 'already' => true]);
        }

        DB::table('galleryables')->insert([
            'gallery_id' => (int) $data['gallery_id'],
            'galleryable_type' => Product::class,
            'galleryable_id' => $product->id,
            'slot' => $slot,
            'sort_order' => $this->nextSortOrder($product, $slot),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditEvent::log('products.gallery.attach', [
            'product_id' => (int) $product->id,
            'gallery_id' => (int) $data['gallery_id'],
            'slot' => $slot,
        ]);

        return response()->json(['ok' => true]);
    }

    public function detach(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'gallery_id' => ['required', 'integer'],
        ]);

        $deleted = DB::table('galleryables')
            ->where('gallery_id', $data['gallery_id'])
            ->where('galleryable_type', Product::class)
            ->where('galleryable_id', $product->id)
            ->delete();

        AuditEvent::log('products.gallery.detach', [
            'product_id' => (int) $product->id,
            'gallery_id' => (int) $data['gallery_id'],
            'deleted' => (int) $deleted,
        ]);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'main_ids' => ['present', 'array'],
            'main_ids.*' => ['integer'],
            'sidebar_ids' => ['present', 'array'],
            'sidebar_ids.*' => ['integer'],
        ]);

        $mainIds = array_values(array_unique($data['main_ids'] ?? []));
        $sidebarIds = array_values(array_unique($data['sidebar_ids'] ?? []));
        $duplicates = array_values(array_intersect($mainIds, $sidebarIds));

        if (!empty($duplicates)) {
            return response()->json([
                'ok' => false,
                'message' => 'Aynı galeri hem ana alan hem yan alan içinde olamaz.',
                'duplicates' => $duplicates,
            ], 422);
        }

        $attached = DB::table('galleryables')
            ->where('galleryable_type', Product::class)
            ->where('galleryable_id', $product->id)
            ->pluck('gallery_id')
            ->all();

        $attachedSet = array_flip($attached);
        $invalid = [];

        foreach (array_merge($mainIds, $sidebarIds) as $galleryId) {
            if (!isset($attachedSet[$galleryId])) {
                $invalid[] = $galleryId;
            }
        }

        if (!empty($invalid)) {
            return response()->json([
                'ok' => false,
                'message' => 'Ürüne bağlı olmayan galeri ID geldi.',
                'invalid' => array_values(array_unique($invalid)),
            ], 422);
        }

        DB::transaction(function () use ($product, $mainIds, $sidebarIds) {
            $this->syncSlotOrder($product, 'main', $mainIds);
            $this->syncSlotOrder($product, 'sidebar', $sidebarIds);
        });

        AuditEvent::log('products.gallery.reorder', [
            'product_id' => (int) $product->id,
            'main_ids' => $mainIds,
            'sidebar_ids' => $sidebarIds,
        ]);

        return response()->json(['ok' => true]);
    }

    private function normalizeSlot(?string $slot): string
    {
        return $slot === 'sidebar' ? 'sidebar' : 'main';
    }

    private function nextSortOrder(Product $product, string $slot): int
    {
        return ((int) DB::table('galleryables')
            ->where('galleryable_type', Product::class)
            ->where('galleryable_id', $product->id)
            ->where('slot', $slot)
            ->max('sort_order')) + 1;
    }

    private function syncSlotOrder(Product $product, string $slot, array $galleryIds): void
    {
        $now = now();

        foreach ($galleryIds as $index => $galleryId) {
            DB::table('galleryables')
                ->where('galleryable_type', Product::class)
                ->where('galleryable_id', $product->id)
                ->where('gallery_id', $galleryId)
                ->update([
                    'slot' => $slot,
                    'sort_order' => $index + 1,
                    'updated_at' => $now,
                ]);
        }
    }
}
