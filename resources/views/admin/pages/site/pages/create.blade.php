@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%]"
        data-page="site.pages.create"
        data-upload-url="{{ route('admin.tinymce.upload') }}"
        data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
        data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
        data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
    >
        @includeIf('admin.partials._flash')

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Yeni Kayıt</span>
                <div>
                    <h1 class="text-xl font-semibold">İçerik Sayfası Oluştur</h1>
                    <div class="text-sm text-muted-foreground">
                        Sayfa içeriğini üret, ön yüz kartlarını besle ve menüye bağlanmaya hazır hale getir.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.site.pages.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button type="submit" form="site-page-create-form" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </div>

        <form
            id="site-page-create-form"
            method="POST"
            action="{{ route('admin.site.pages.store') }}"
            enctype="multipart/form-data"
            class="grid gap-6"
        >
            @csrf

            @include('admin.pages.site.pages.partials._form', ['page' => null])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.site.pages.index') }}" class="kt-btn kt-btn-light">İptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
