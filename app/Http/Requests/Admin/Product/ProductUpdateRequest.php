<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Validation\Rule;

class ProductUpdateRequest extends ProductStoreRequest
{
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : $product;

        return array_merge(parent::rules(), [
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($productId)],
        ]);
    }
}
