<?php

namespace App\Http\Requests\Admin\Project;

use App\Models\Admin\Project\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectStoreRequest extends FormRequest
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

        if (!$this->has('status') || !$this->input('status')) {
            $this->merge(['status' => Project::STATUS_APPOINTMENT_PENDING]);
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
            'content' => trim((string) $this->input('content', '')),
            'meta_title' => trim((string) $this->input('meta_title', '')),
            'meta_description' => trim((string) $this->input('meta_description', '')),
            'meta_keywords' => trim((string) $this->input('meta_keywords', '')),
            'status' => trim((string) $this->input('status', Project::STATUS_APPOINTMENT_PENDING)),
            'category_ids' => $categoryIds,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(array_keys(Project::STATUS_OPTIONS))],
            'is_featured' => ['nullable', 'boolean'],
            'appointment_id' => ['nullable', 'integer', 'min:1'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'featured_image' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
            'clear_featured_image' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
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
