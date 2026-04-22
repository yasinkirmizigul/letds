@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%]" data-page="site.sliders.create">
        @includeIf('admin.partials._flash')

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm kt-badge-light-primary w-fit">Yeni Kayıt</span>
                <div>
                    <h1 class="text-xl font-semibold">Slider Kaydı Oluştur</h1>
                    <div class="text-sm text-muted-foreground">Başlık, CTA, görsel çerçevesi ve ton ayarlarını birlikte kurgula.</div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.site.sliders.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button type="submit" form="home-slider-create-form" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </div>

        <form id="home-slider-create-form" method="POST" action="{{ route('admin.site.sliders.store') }}" enctype="multipart/form-data" class="grid gap-6">
            @csrf
            @include('admin.pages.site.sliders.partials._form', ['slider' => null, 'themeOptions' => $themeOptions])
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.site.sliders.index') }}" class="kt-btn kt-btn-light">İptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
