<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Product\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $this->validateVariant($request);
        $product->variants()->create($validated);

        return $this->respond($request, 'Varyant oluşturuldu.');
    }

    public function update(Request $request, ProductVariant $variant): JsonResponse|RedirectResponse
    {
        $validated = $this->validateVariant($request, $variant);
        $variant->update($validated);

        return $this->respond($request, 'Varyant güncellendi.');
    }

    public function destroy(Request $request, ProductVariant $variant): JsonResponse|RedirectResponse
    {
        $variant->delete();

        return $this->respond($request, 'Varyant silindi.');
    }

    private function validateVariant(Request $request, ?ProductVariant $variant = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'sku' => ['nullable', 'string', 'max:120', Rule::unique('product_variants', 'sku')->ignore($variant?->id)],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('product_variants', 'barcode')->ignore($variant?->id)],
            'option_values_text' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['option_values'] = collect(preg_split('/[,;\r\n]+/u', (string) ($validated['option_values_text'] ?? '')))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
        unset($validated['option_values_text']);

        $validated['currency'] = strtoupper(trim((string) ($validated['currency'] ?? ''))) ?: 'TRY';
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 1);
        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;

        return $validated;
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect_url' => route('admin.ecommerce.inventory.index'),
            ]);
        }

        return back()->with('success', $message);
    }
}
