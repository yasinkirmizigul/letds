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
        // status default (create form)
        if (!$this->has('status') || !$this->input('status')) {
            $this->merge(['status' => Project::STATUS_APPOINTMENT_PENDING]);
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

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],

            // ✅ workflow status
            'status' => ['required', 'string', Rule::in(array_keys(Project::STATUS_OPTIONS))],

            // ✅ homepage toggle
            'is_featured' => ['nullable', 'boolean'],

            'appointment_id' => ['nullable', 'integer', 'min:1'],

            // dikkat: senin projende tablo adı "media" (Media modeli)
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],

            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'min:1', 'exists:categories,id'],
        ];
    }
}
