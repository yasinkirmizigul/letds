@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed max-w-[90%]" data-page="categories.create">
        @includeIf('admin.partials._flash')

        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">{{ $pageTitle ?? 'Yeni Kategori' }}</h1>
                <div class="text-sm text-muted-foreground">Blog, proje ve ürünlerde ortak kullanılan kategori yapısı.</div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button type="submit" form="category-create-form" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </div>

        <form id="category-create-form" method="POST" action="{{ route('admin.categories.store') }}" class="grid gap-6">
            @csrf
            @include('admin.pages.categories.partials.form', [
                'category' => null,
                'parentOptions' => $parentOptions ?? [],
            ])

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-light">İptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
@endsection
