<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Admin\Product\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // status default (create form)
        if (!$this->has('status') || !$this->input('status')) {
            $this->merge(['status' => Product::STATUS_APPOINTMENT_PENDING]);
        }

        // checkbox normalize
        if (!$this->has('is_featured')) {
            $this->merge(['is_featured' => 0]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],

            'content' => ['nullable', 'string'],

            // ✅ optional product fields
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],

                        'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'vat_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'brand' => ['nullable', 'string', 'max:120'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],

            // ✅ workflow status
            'status' => ['required', 'string', Rule::in(array_keys(Product::STATUS_OPTIONS))],

            // ✅ homepage toggle
            'is_featured' => ['nullable', 'boolean'],

            'appointment_id' => ['nullable', 'integer', 'min:1'],

            // tablo adı "media"
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],

            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'min:1', 'exists:categories,id'],
        ];
    }
}
