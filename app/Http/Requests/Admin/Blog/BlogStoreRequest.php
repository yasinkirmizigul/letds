<?php

namespace App\Http\Requests\Admin\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $categoryIds = $this->input('category_ids', []);

        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $categoryIds = array_values(array_unique(array_filter(array_map(function ($value) {
            $value = (int) $value;
            return $value > 0 ? $value : null;
        }, $categoryIds))));

        if (!$this->has('is_published')) {
            $this->merge(['is_published' => 0]);
        }

        if (!$this->has('is_featured')) {
            $this->merge(['is_featured' => 0]);
        }

        if (!$this->has('clear_featured_image')) {
            $this->merge(['clear_featured_image' => 0]);
        }

        $this->merge([
            'title' => trim((string) $this->input('title', '')),
            'slug' => trim((string) $this->input('slug', '')),
            'excerpt' => trim((string) $this->input('excerpt', '')),
            'meta_title' => trim((string) $this->input('meta_title', '')),
            'meta_description' => trim((string) $this->input('meta_description', '')),
            'meta_keywords' => trim((string) $this->input('meta_keywords', '')),
            'category_ids' => $categoryIds,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'clear_featured_image' => ['nullable', 'boolean'],
        ];
    }
}
