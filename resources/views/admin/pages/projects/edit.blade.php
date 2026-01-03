@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed"
         data-page="projects.edit"
         data-id="{{ $project->id }}">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold">{{ $pageTitle ?? 'Proje Düzenle' }}</h1>
                    <div class="text-sm text-muted-foreground">ID: {{ $project->id }} • Slug: {{ $project->slug }}</div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.projects.index') }}" class="kt-btn kt-btn-light">Geri</a>

                    <button type="button" class="kt-btn kt-btn-danger" id="projectDeleteBtn">
                        Sil
                    </button>
                </div>
            </div>

            <div class="kt-card">
                <form class="kt-card-content p-8 flex flex-col gap-6"
                      method="POST"
                      action="{{ route('admin.projects.update', $project) }}">
                    @csrf
                    @method('PUT')

                    @include('admin.pages.projects.partials._form', [
                        'project' => $project,
                        'categories' => $categories ?? collect(),
                        'selectedCategoryIds' => $selectedCategoryIds ?? [],
                        'featuredMediaId' => $featuredMediaId ?? null,
                    ])

                    <div class="flex items-center gap-2">
                        <button class="kt-btn kt-btn-primary" type="submit">Güncelle</button>
                    </div>
                </form>
            </div>

            {{-- Gallery panel --}}
            @include('admin.pages.projects.partials._gallery', ['project' => $project])

            {{-- Media upload modal --}}
            @include('admin.pages.media.partials._upload-modal')
        </div>
    </div>
@endsection
