@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[90%]" data-page="categories.edit">
        @includeIf('admin.partials._flash')

        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">{{ $pageTitle ?? 'Kategori Düzenle' }}</h1>
                <div class="text-sm text-muted-foreground">ID: {{ $category->id }} • Slug: {{ $category->slug }}</div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button type="submit" form="category-update-form" class="kt-btn kt-btn-primary">Güncelle</button>
                @perm('categories.delete')
                    <button type="button" class="kt-btn kt-btn-destructive" data-kt-modal-target="#deleteCategoryModal">Sil</button>
                @endperm
            </div>
        </div>

        <form id="category-update-form" method="POST" action="{{ route('admin.categories.update', ['category' => $category->id]) }}" class="grid gap-6">
            @csrf
            @method('PUT')
            @include('admin.pages.categories.partials.form', [
                'category' => $category,
                'parentOptions' => $parentOptions ?? [],
            ])

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">İptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
            </div>
        </form>

        @perm('categories.delete')
            <form id="category-delete-form" method="POST" action="{{ route('admin.categories.destroy', ['category' => $category->id]) }}">
                @csrf
                @method('DELETE')
            </form>

            <div id="deleteCategoryModal" class="kt-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="kt-card max-w-md">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Kategori Sil</h3>
                    </div>
                    <div class="kt-card-content">
                        <p class="text-sm text-muted-foreground">
                            Bu kategoriyi silmek istediğine emin misin?
                            <br>
                            <strong>Alt kategoriler varsa silinemez.</strong>
                        </p>
                    </div>
                    <div class="kt-card-footer flex justify-end gap-2">
                        <button type="button" class="kt-btn kt-btn-light" data-kt-modal-close>Vazgeç</button>
                        <button type="submit" form="category-delete-form" class="kt-btn kt-btn-destructive">Evet, Sil</button>
                    </div>
                </div>
            </div>
        @endperm
    </div>
@endsection
