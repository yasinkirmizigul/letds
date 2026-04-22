@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[96%]" data-page="site.sliders.edit">
        @includeIf('admin.partials._flash')

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm {{ $slider->is_active ? 'kt-badge-light-success' : 'kt-badge-light' }} w-fit">
                    {{ $slider->is_active ? 'Aktif Slider' : 'Pasif Slider' }}
                </span>
                <div>
                    <h1 class="text-xl font-semibold">Slider Kaydını Düzenle</h1>
                    <div class="text-sm text-muted-foreground">#{{ $slider->id }} • {{ $slider->title }}</div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.site.sliders.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button type="submit" form="home-slider-update-form" class="kt-btn kt-btn-primary">Güncelle</button>
            </div>
        </div>

        <form id="home-slider-update-form" method="POST" action="{{ route('admin.site.sliders.update', $slider) }}" enctype="multipart/form-data" class="grid gap-6">
            @csrf
            @method('PUT')
            @include('admin.pages.site.sliders.partials._form', ['slider' => $slider, 'themeOptions' => $themeOptions])
            <div class="flex items-center justify-between gap-3">
                <button type="submit" form="home-slider-delete-form" class="kt-btn kt-btn-danger" onclick="return confirm('Bu slider kaydı silinsin mi?')">
                    Sliderı Sil
                </button>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.site.sliders.index') }}" class="kt-btn kt-btn-light">İptal</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
                </div>
            </div>
        </form>

        <form id="home-slider-delete-form" method="POST" action="{{ route('admin.site.sliders.destroy', $slider) }}">
            @csrf
            @method('DELETE')
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
