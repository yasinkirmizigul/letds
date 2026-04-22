@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%]"
        data-page="products.create"
        data-upload-url="{{ route('admin.tinymce.upload') }}"
        data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
        data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
        data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
        data-slug-check-url="{{ route('admin.products.checkSlug') }}"
        data-slug-ignore-id=""
        data-status-options='@json($statusOptions)'
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light w-fit">Yeni Ürün</span>
                <div>
                    <h1 class="text-xl font-semibold">Ürün Oluştur</h1>
                    <div class="text-sm text-muted-foreground">
                        Workflow, fiyat, SEO ve vitrin yönetimini tek ekrandan kurun.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button class="kt-btn kt-btn-primary" type="submit" form="product-create-form">Kaydet</button>
            </div>
        </div>

        <form
            id="product-create-form"
            method="POST"
            action="{{ route('admin.products.store') }}"
            enctype="multipart/form-data"
            class="grid gap-6"
        >
            @csrf

            @include('admin.pages.products.partials._form', [
                'product' => null,
                'categoryOptions' => $categoryOptions ?? [],
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => null,
                'statusOptions' => $statusOptions ?? [],
            ])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="kt-btn kt-btn-light">İptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
