@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%]"
        data-page="products.edit"
        data-upload-url="{{ route('admin.tinymce.upload') }}"
        data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
        data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
        data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
        data-slug-check-url="{{ route('admin.products.checkSlug') }}"
        data-slug-ignore-id="{{ $product->id }}"
        data-status-options='@json($statusOptions)'
        data-product-index-url="{{ route('admin.products.index') }}"
        data-product-delete-url="{{ route('admin.products.destroy', $product) }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm {{ $product->is_featured ? 'kt-badge-light-success' : 'kt-badge-light' }} w-fit">
                    {{ $product->is_featured ? 'Anasayfada' : 'Normal Kayıt' }}
                </span>
                <div>
                    <h1 class="text-xl font-semibold">Ürünü Düzenle</h1>
                    <div class="text-sm text-muted-foreground">#{{ $product->id }} - {{ $product->title }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button class="kt-btn kt-btn-primary" type="submit" form="product-update-form">Kaydet</button>
                @perm('products.delete')
                    <button type="button" id="productDeleteBtn" class="kt-btn kt-btn-danger">Sil</button>
                @endperm
            </div>
        </div>

        <form
            id="product-update-form"
            method="POST"
            action="{{ route('admin.products.update', $product) }}"
            enctype="multipart/form-data"
            class="grid gap-6"
        >
            @csrf
            @method('PUT')

            @include('admin.pages.products.partials._form', [
                'product' => $product,
                'categoryOptions' => $categoryOptions ?? [],
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => $featuredMediaId ?? null,
                'statusOptions' => $statusOptions ?? [],
            ])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">İptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
            </div>
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
