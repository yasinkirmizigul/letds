@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%]"
        data-page="blog.edit"
        data-upload-url="{{ route('admin.tinymce.upload') }}"
        data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
        data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
        data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
        data-slug-check-url="{{ route('admin.blog.checkSlug') }}"
        data-slug-ignore-id="{{ $blogPost->id }}"
        data-blog-index-url="{{ route('admin.blog.index') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm {{ $blogPost->is_published ? 'kt-badge-light-success' : 'kt-badge-light' }} w-fit">
                    {{ $blogPost->is_published ? 'Yayinda' : 'Taslak' }}
                </span>
                <div>
                    <h1 class="text-xl font-semibold">Blog Yazisini Duzenle</h1>
                    <div class="text-sm text-muted-foreground">#{{ $blogPost->id }} - {{ $blogPost->title }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button class="kt-btn kt-btn-primary" type="submit" form="blog-update-form">Kaydet</button>

                @perm('blog.delete')
                    <form id="blog-delete-form" method="POST" action="{{ route('admin.blog.destroy', $blogPost) }}">
                        @csrf
                        @method('DELETE')
                        <button class="kt-btn kt-btn-danger" type="submit">Sil</button>
                    </form>
                @endperm
            </div>
        </div>

        <form
            id="blog-update-form"
            method="POST"
            action="{{ route('admin.blog.update', $blogPost) }}"
            enctype="multipart/form-data"
            class="grid gap-6"
        >
            @csrf
            @method('PUT')

            @include('admin.pages.blog.partials._form', [
                'blogPost' => $blogPost,
                'categoryOptions' => $categoryOptions ?? [],
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => old('featured_media_id', $blogPost->featuredMediaOne()?->id),
                'currentFeaturedUrl' => $blogPost->featuredMediaUrl() ?? $blogPost->featured_image_url,
            ])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">Iptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Guncelle</button>
            </div>
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
