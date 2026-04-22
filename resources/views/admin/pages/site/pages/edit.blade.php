@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%]"
        data-page="site.pages.edit"
        data-upload-url="{{ route('admin.tinymce.upload') }}"
        data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
        data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
        data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm {{ $page->is_featured ? 'kt-badge-light-primary' : 'kt-badge-light' }} w-fit">
                    {{ $page->is_featured ? 'Öne Çıkan Sayfa' : 'İçerik Sayfası' }}
                </span>
                <div>
                    <h1 class="text-xl font-semibold">İçerik Sayfasını Düzenle</h1>
                    <div class="text-sm text-muted-foreground">#{{ $page->id }} • {{ $page->title }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.site.pages.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <a href="{{ $page->publicUrl() }}" target="_blank" class="kt-btn kt-btn-light">Ön Yüzde Gör</a>
                <button type="submit" form="site-page-update-form" class="kt-btn kt-btn-primary">Güncelle</button>
            </div>
        </div>

        <form
            id="site-page-update-form"
            method="POST"
            action="{{ route('admin.site.pages.update', $page) }}"
            enctype="multipart/form-data"
            class="grid gap-6"
        >
            @csrf
            @method('PUT')

            @include('admin.pages.site.pages.partials._form', ['page' => $page])

            <div class="flex items-center justify-between gap-3">
                <button type="submit" form="site-page-delete-form" class="kt-btn kt-btn-danger" onclick="return confirm('Bu sayfa silinsin mi?')">
                    Sayfayı Sil
                </button>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.site.pages.index') }}" class="kt-btn kt-btn-light">İptal</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
                </div>
            </div>
        </form>

        <form id="site-page-delete-form" method="POST" action="{{ route('admin.site.pages.destroy', $page) }}">
            @csrf
            @method('DELETE')
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
