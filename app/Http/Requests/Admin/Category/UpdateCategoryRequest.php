<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\CategoryTree;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC kullanıyorsan burada yetki kontrolü yap
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->route('category'); // Route model binding: Category $category
            if (!$category) return;

            $parentId = $this->input('parent_id');

            // null / boş ise sorun yok
            if ($parentId === null || $parentId === '' ) {
                return;
            }

            $parentId = (int) $parentId;

            // Kendisine parent seçmek = direkt cycle
            if ($parentId === (int) $category->id) {
                $validator->errors()->add('parent_id', 'Kategori kendisini üst kategori olarak seçemez.');
                return;
            }

            // Tek query + RAM hesap
            $all = CategoryTree::all();
            $byParent = CategoryTree::indexByParent($all);
            $descendantIds = CategoryTree::descendantIdsFromAll((int)$category->id, $byParent);

            // Parent, kendi altlarından biri olamaz
            if (in_array($parentId, $descendantIds, true)) {
                $validator->errors()->add('parent_id', 'Kategori kendi alt kategorilerinden birini üst kategori olarak seçemez.');
            }
        });
    }
}
