@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed"
         data-page="projects.create"
         data-upload-url="{{ Route::has('admin.tinymce.upload') ? route('admin.tinymce.upload') : url('/admin/tinymce/upload') }}"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ url('/assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}">

        @includeIf('admin.partials._flash')

        {{-- Başlık/desc/actions kısmını sende nasıl standard ise ona göre bırakıyorum --}}
        <form method="POST" action="{{ route('admin.projects.store') }}" class="grid gap-5 lg:gap-7.5">
            @csrf

            @include('admin.pages.projects.partials._form', [
                'project' => null,
                'categories' => $categories ?? collect(),
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => null,
            ])

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
                <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">İptal</a>
            </div>
        </form>
    </div>

    {{-- Media upload modal --}}
    @include('admin.pages.media.partials._upload-modal')
@endsection
s
