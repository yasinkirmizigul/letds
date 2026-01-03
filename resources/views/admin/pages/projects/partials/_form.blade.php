@php
    $project = $project ?? null;

    $categories = $categories ?? collect();
    $selectedCategoryIds = $selectedCategoryIds ?? [];
    $featuredMediaId = $featuredMediaId ?? null;

    $st = old('status', $project->status ?? 'draft');

    $slugVal = old('slug', $project->slug ?? '');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT --}}
    <div class="lg:col-span-2 flex flex-col gap-6">

        <div>
            <label class="kt-form-label">Başlık</label>
            <input class="kt-input"
                   name="title"
                   id="projectTitle"
                   value="{{ old('title', $project->title ?? '') }}"/>
            @error('title') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="kt-form-label">Slug</label>

                <div class="flex items-center gap-2">
                    <input class="kt-input"
                           name="slug"
                           id="projectSlug"
                           value="{{ $slugVal }}"/>
                    <button type="button" class="kt-btn kt-btn-light shrink-0" id="projectSlugGenBtn">
                        Otomatik
                    </button>
                </div>

                <div class="text-xs text-muted-foreground mt-2">
                    URL Önizleme:
                    <span class="font-medium">
                        {{ url('/projects') }}/<span id="projectSlugPreview">{{ $slugVal }}</span>
                    </span>
                </div>

                @error('slug') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="kt-form-label">Status</label>
                <select class="kt-select" name="status" id="projectStatus" data-kt-select="true">
                    <option value="draft" @selected($st === 'draft')>draft</option>
                    <option value="active" @selected($st === 'active')>active</option>
                    <option value="archived" @selected($st === 'archived')>archived</option>
                </select>
                @error('status') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <label class="kt-form-label font-normal text-mono">İçerik</label>
            <textarea id="content_editor"
                      name="content"
                      class="kt-input min-h-[320px]">{{ old('content', $project->content ?? '') }}</textarea>
            @error('content')
            <div class="text-xs text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="kt-card kt-card-border">
            <div class="kt-card-header">
                <h3 class="kt-card-title">SEO</h3>
            </div>
            <div class="kt-card-content p-6 flex flex-col gap-4">
                <div>
                    <label class="kt-form-label">Meta Title</label>
                    <input class="kt-input"
                           name="meta_title"
                           value="{{ old('meta_title', $project->meta_title ?? '') }}"/>
                    @error('meta_title') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="kt-form-label">Meta Description</label>
                    <textarea class="kt-textarea"
                              name="meta_description">{{ old('meta_description', $project->meta_description ?? '') }}</textarea>
                    @error('meta_description') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="kt-form-label">Meta Keywords</label>
                    <input class="kt-input"
                           name="meta_keywords"
                           value="{{ old('meta_keywords', $project->meta_keywords ?? '') }}"/>
                    @error('meta_keywords') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="lg:col-span-1 flex flex-col gap-6">

        <div class="kt-card kt-card-border">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Kategoriler</h3>
            </div>

            <div class="kt-card-content p-6 flex flex-col gap-2">
                <select name="category_ids[]" multiple
                        class="kt-select @error('category_ids') kt-input-invalid @enderror"
                        data-kt-select="true"
                        data-kt-select-placeholder="Kategoriler"
                        data-kt-select-multiple="true"
                        data-kt-select-tags="true"
                        data-kt-select-config='{
                            "showSelectedCount": true,
                            "enableSelectAll": true,
                            "selectAllText": "Tümünü Seç",
                            "clearAllText": "Tümünü Temizle"
                        }'>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}"
                            @selected(in_array((int)$c->id, old('category_ids', $selectedCategoryIds)))>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_ids')
                <div class="text-xs text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Gallery panel --}}
        @include('admin.pages.projects.partials._gallery', ['project' => $project])

        <div class="kt-card kt-card-border">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Öne Çıkan Görsel</h3>
            </div>

            <div class="kt-card-content p-6 flex flex-col gap-3">
                <input type="hidden"
                       name="featured_media_id"
                       id="projectFeaturedMediaId"
                       value="{{ old('featured_media_id', $featuredMediaId ?? '') }}"/>

                <img id="projectFeaturedPreview"
                     class="rounded-lg border hidden"
                     alt="preview"/>

                <div class="flex items-center gap-2">
                    <button type="button"
                            class="kt-btn kt-btn-light"
                            data-media-picker="true"
                            data-media-picker-target="#projectFeaturedMediaId"
                            data-media-picker-preview="#projectFeaturedPreview"
                            data-media-picker-mime="image/">
                        Media’dan Seç
                    </button>

                    <button type="button"
                            class="kt-btn kt-btn-light"
                            id="projectFeaturedClearBtn">
                        Temizle
                    </button>
                </div>

                @error('featured_media_id') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="kt-card kt-card-border">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Appointment</h3>
            </div>
            <div class="kt-card-content p-6">
                <input class="kt-input"
                       name="appointment_id"
                       placeholder="opsiyonel"
                       value="{{ old('appointment_id', $project->appointment_id ?? '') }}"/>
                @error('appointment_id') <div class="text-danger text-sm mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

    </div>
</div>
