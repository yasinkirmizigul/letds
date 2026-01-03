<?php

namespace App\Http\Requests\Admin\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'archived'])],

            'appointment_id' => ['nullable', 'integer', 'min:1'],

            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],

            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Normalize
        if (!isset($data['status']) || !$data['status']) {
            $data['status'] = 'draft';
        }

        return $data;
    }
}
