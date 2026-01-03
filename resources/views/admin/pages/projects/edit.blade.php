@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed"
         data-page="projects.edit"
         data-id="{{ $project->id }}"
         data-upload-url="{{ \Illuminate\Support\Facades\Route::has('admin.tinymce.upload') ? route('admin.tinymce.upload') : url('/admin/tinymce/upload') }}"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ url('/assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}">

        @includeIf('admin.partials._flash')

        <form method="POST"
              action="{{ route('admin.projects.update', $project) }}"
              class="grid gap-5 lg:gap-7.5">
            @csrf
            @method('PUT')

            @include('admin.pages.projects.partials._form', [
                'project' => $project,
                'categories' => $categories ?? collect(),
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => $featuredMediaId ?? null,
            ])

            <div class="flex items-center justify-between gap-3">
                <button type="button" id="projectDeleteBtn" class="kt-btn kt-btn-danger">Sil</button>

                <div class="flex items-center gap-3">
                    <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
                    <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">Geri</a>
                </div>
            </div>
        </form>

        {{-- Gallery panel --}}
        @include('admin.pages.projects.partials._gallery', ['project' => $project])
    </div>

    {{-- Media upload modal --}}
    @include('admin.pages.media.partials._upload-modal')
@endsection

