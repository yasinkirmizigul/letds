@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed" data-page="projects.create">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold">{{ $pageTitle ?? 'Proje Ekle' }}</h1>
                    <div class="text-sm text-muted-foreground">Başlık, içerik, SEO, kategori, görsel</div>
                </div>
                <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">Geri</a>
            </div>

            <div class="kt-card">
                <form class="kt-card-content p-8 flex flex-col gap-6"
                      method="POST"
                      action="{{ route('admin.projects.store') }}">
                    @csrf

                    @include('admin.pages.projects.partials._form', [
                        'project' => null,
                        'categories' => $categories ?? collect(),
                        'selectedCategoryIds' => $selectedCategoryIds ?? [],
                        'featuredMediaId' => null,
                    ])

                    <div class="flex items-center gap-2">
                        <button class="kt-btn kt-btn-primary" type="submit">Kaydet</button>
                        <a class="kt-btn kt-btn-light" href="{{ route('admin.projects.index') }}">İptal</a>
                    </div>
                </form>
            </div>

            {{-- Media upload modal (media picker buna ihtiyaç duyuyor) --}}
            @include('admin.pages.media.partials._upload-modal')
        </div>
    </div>
@endsection
