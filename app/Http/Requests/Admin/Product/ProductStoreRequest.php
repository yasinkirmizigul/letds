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
        $trimmed = [];

        foreach ([
            'title',
            'slug',
            'content',
            'sku',
            'barcode',
            'currency',
            'brand',
            'meta_title',
            'meta_description',
            'meta_keywords',
        ] as $field) {
            if ($this->has($field)) {
                $trimmed[$field] = is_string($this->input($field))
                    ? trim((string) $this->input($field))
                    : $this->input($field);
            }
        }

        if (array_key_exists('currency', $trimmed) && is_string($trimmed['currency'])) {
            $trimmed['currency'] = strtoupper($trimmed['currency']);
        }

        if (!$this->has('status') || !$this->input('status')) {
            $trimmed['status'] = Product::STATUS_APPOINTMENT_PENDING;
        }

        if (!$this->has('is_featured')) {
            $trimmed['is_featured'] = 0;
        }

        if (!$this->has('is_active')) {
            $trimmed['is_active'] = 0;
        }

        if (!$this->has('clear_featured_image')) {
            $trimmed['clear_featured_image'] = 0;
        }

        $categoryIds = collect($this->input('category_ids', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $trimmed['category_ids'] = $categoryIds;

        $this->merge($trimmed);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],

            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'brand' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'size:3'],

            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'vat_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'clear_featured_image' => ['nullable', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],

            'status' => ['required', 'string', Rule::in(array_keys(Product::STATUS_OPTIONS))],
            'appointment_id' => ['nullable', 'integer', 'min:1'],

            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'featured_image' => ['nullable', 'image', 'max:5120'],

            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'translations' => ['nullable', 'array'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_keywords' => ['nullable', 'string', 'max:500'],
        ];
    }
}
