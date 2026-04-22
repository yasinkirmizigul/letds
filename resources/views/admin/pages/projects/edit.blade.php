@extends('admin.layouts.main.app')

@section('content')
    <div
        class="kt-container-fixed max-w-[96%]"
        data-page="projects.edit"
        data-upload-url="{{ route('admin.tinymce.upload') }}"
        data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
        data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
        data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
        data-slug-check-url="{{ route('admin.projects.checkSlug') }}"
        data-slug-ignore-id="{{ $project->id }}"
        data-status-options='@json($statusOptions)'
        data-public-statuses='@json(array_values($publicStatuses ?? []))'
        data-project-index-url="{{ route('admin.projects.index') }}"
        data-project-delete-url="{{ route('admin.projects.destroy', $project) }}"
    >
        @includeIf('admin.partials._flash')

        <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-2">
                <span class="kt-badge kt-badge-sm {{ $project->is_featured ? 'kt-badge-light-success' : 'kt-badge-light' }} w-fit">
                    {{ $project->is_featured ? 'Anasayfada' : 'Normal Kayıt' }}
                </span>
                <div>
                    <h1 class="text-xl font-semibold">Projeyi Düzenle</h1>
                    <div class="text-sm text-muted-foreground">#{{ $project->id }} - {{ $project->title }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button class="kt-btn kt-btn-primary" type="submit" form="project-update-form">Kaydet</button>
                @perm('projects.delete')
                    <button type="button" id="projectDeleteBtn" class="kt-btn kt-btn-danger">Sil</button>
                @endperm
            </div>
        </div>

        <form
            id="project-update-form"
            method="POST"
            action="{{ route('admin.projects.update', $project) }}"
            enctype="multipart/form-data"
            class="grid gap-6"
        >
            @csrf
            @method('PUT')

            @include('admin.pages.projects.partials._form', [
                'project' => $project,
                'categoryOptions' => $categoryOptions ?? [],
                'selectedCategoryIds' => $selectedCategoryIds ?? [],
                'featuredMediaId' => $featuredMediaId ?? null,
                'statusOptions' => $statusOptions ?? [],
                'publicStatuses' => $publicStatuses ?? [],
            ])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">İptal</a>
                <button type="submit" class="kt-btn kt-btn-primary">Güncelle</button>
            </div>
        </form>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
