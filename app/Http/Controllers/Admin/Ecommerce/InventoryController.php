<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Admin\Ecommerce\InventoryMovement;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Product\ProductVariant;
use App\Services\Admin\AdminNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));

        $products = Product::query()
            ->with('variants')
            ->search($search)
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        $movements = InventoryMovement::query()
            ->with(['product:id,title,sku,stock', 'productVariant:id,product_id,title,sku,stock', 'user:id,name'])
            ->latest('occurred_at')
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.pages.ecommerce.inventory.index', [
            'pageTitle' => 'Stok ve Varyant Yönetimi',
            'products' => $products,
            'movements' => $movements,
            'search' => $search,
            'movementTypeOptions' => InventoryMovement::typeOptions(),
            'stats' => [
                'products' => Product::query()->count(),
                'variants' => ProductVariant::query()->count(),
                'low_products' => Product::query()->lowStock()->count(),
                'low_variants' => ProductVariant::query()->lowStock()->count(),
            ],
        ]);
    }

    public function storeMovement(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'target_type' => ['required', Rule::in(['product', 'variant'])],
            'target_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(array_keys(InventoryMovement::typeOptions()))],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $movement = DB::transaction(function () use ($validated, $request) {
            $quantity = (float) $validated['quantity'];
            $signedQuantity = InventoryMovement::signedQuantity((string) $validated['type'], $quantity);

            if ($validated['target_type'] === 'variant') {
                $target = ProductVariant::query()->lockForUpdate()->findOrFail((int) $validated['target_id']);
                $before = is_null($target->stock) ? 0.0 : (float) $target->stock;
                $after = max(0, round($before + $signedQuantity, 3));
                $target->forceFill(['stock' => $after])->save();

                $movement = InventoryMovement::query()->create([
                    'product_id' => $target->product_id,
                    'product_variant_id' => $target->id,
                    'user_id' => $request->user()?->id,
                    'type' => $validated['type'],
                    'reason' => $validated['reason'] ?? null,
                    'quantity' => $signedQuantity,
                    'before_stock' => $before,
                    'after_stock' => $after,
                    'reference' => $validated['reference'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'occurred_at' => now(),
                ]);

                $this->notifyLowStock($target, $after);

                return $movement;
            }

            $target = Product::query()->lockForUpdate()->findOrFail((int) $validated['target_id']);
            $before = is_null($target->stock) ? 0.0 : (float) $target->stock;
            $after = max(0, round($before + $signedQuantity, 3));
            $target->forceFill(['stock' => (int) round($after)])->save();

            $movement = InventoryMovement::query()->create([
                'product_id' => $target->id,
                'user_id' => $request->user()?->id,
                'type' => $validated['type'],
                'reason' => $validated['reason'] ?? null,
                'quantity' => $signedQuantity,
                'before_stock' => $before,
                'after_stock' => $after,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'occurred_at' => now(),
            ]);

            if ($after <= 5) {
                app(AdminNotificationService::class)->notifyBackoffice([
                    'type' => 'inventory',
                    'severity' => 'warning',
                    'title' => 'Ürün stoku kritik seviyeye indi',
                    'body' => $target->title . ' · Kalan stok: ' . number_format($after, 3, ',', '.'),
                    'action_label' => 'Stok yönetimine git',
                    'action_url' => route('admin.ecommerce.inventory.index', ['q' => $target->sku ?: $target->title]),
                    'source_type' => Product::class,
                    'source_id' => $target->id,
                ], 'ecommerce_inventory.view');
            }

            return $movement;
        });

        return $this->respond($request, 'Stok hareketi kaydedildi.', [
            'movement_id' => $movement->id,
            'redirect_url' => route('admin.ecommerce.inventory.index'),
        ]);
    }

    private function notifyLowStock(ProductVariant $variant, float $after): void
    {
        if ($after > (float) $variant->low_stock_threshold) {
            return;
        }

        $variant->loadMissing('product');

        app(AdminNotificationService::class)->notifyBackoffice([
            'type' => 'inventory',
            'severity' => 'warning',
            'title' => 'Varyant stoku kritik seviyeye indi',
            'body' => ($variant->product?->title ?: 'Ürün') . ' / ' . $variant->title . ' · Kalan stok: ' . number_format($after, 3, ',', '.'),
            'action_label' => 'Stok yönetimine git',
            'action_url' => route('admin.ecommerce.inventory.index', ['q' => $variant->sku ?: $variant->title]),
            'source_type' => ProductVariant::class,
            'source_id' => $variant->id,
        ], 'ecommerce_inventory.view');
    }

    private function respond(Request $request, string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'ok' => true,
                'message' => $message,
            ], $extra));
        }

        return back()->with('success', $message);
    }
}
