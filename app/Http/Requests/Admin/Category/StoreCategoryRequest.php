<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC varsa burada kontrol et
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // select boş string dönüyorsa null’a çevir
        if ($this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }
    }
}
