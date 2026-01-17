@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6"
         data-page="blog.edit"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}"
         data-upload-url="{{ route('admin.tinymce.upload') }}">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Blog Düzenle</h1>
                <div class="text-sm text-muted-foreground">#{{ $blogPost->id }} • {{ $blogPost->title }}</div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">Geri</a>

                <button class="kt-btn kt-btn-primary" type="submit" form="blog-update-form">Kaydet</button>

                <form id="blog-delete-form" method="POST"
                      action="{{ route('admin.blog.destroy', ['blogPost' => $blogPost->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="kt-btn kt-btn-danger" type="submit">Sil</button>
                </form>
            </div>
        </div>

        @php
            $categories = $categories ?? collect();
            $selectedCategoryIds = $selectedCategoryIds ?? [];
        @endphp

        <div class="kt-card">
            <form id="blog-update-form"
                  class="kt-card-content p-8 flex flex-col gap-6"
                  method="POST"
                  action="{{ route('admin.blog.update', ['blogPost' => $blogPost->id]) }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Left --}}
                    <div class="lg:col-span-2 flex flex-col gap-6">

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Başlık</label>
                            <input id="title" name="title"
                                   class="kt-input @error('title') kt-input-invalid @enderror"
                                   value="{{ old('title', $blogPost->title) }}">
                            @error('title')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Slug</label>

                            <div class="flex items-center gap-2">
                                <input id="slug" name="slug"
                                       class="kt-input @error('slug') kt-input-invalid @enderror"
                                       value="{{ old('slug', $blogPost->slug) }}">

                                <button type="button" id="slug_regen" class="kt-btn kt-btn-light">Oluştur</button>

                                <label class="kt-switch shrink-0" title="Otomatik slug">
                                    <input type="checkbox" id="slug_auto" checked>
                                    <span class="kt-switch-slider"></span>
                                </label>
                            </div>

                            @error('slug')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror

                            <div class="text-sm text-muted-foreground">
                                URL Önizleme:
                                <span class="font-medium">{{ url('/blog') }}/<span
                                        id="url_slug_preview">{{ old('slug', $blogPost->slug) }}</span></span>
                            </div>

                            <div id="slugCheckHint" class="text-xs text-muted-foreground"></div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Özet</label>
                            <textarea name="excerpt" rows="3"
                                      class="kt-textarea @error('excerpt') kt-input-invalid @enderror">{{ old('excerpt', $blogPost->excerpt) }}</textarea>
                            @error('excerpt')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">İçerik</label>
                            <textarea id="content_editor" name="content"
                                      class="kt-textarea @error('content') kt-input-invalid @enderror">{{ old('content', $blogPost->content) }}</textarea>
                            @error('content')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="kt-form-label font-normal text-mono">Meta Title</label>
                                <input name="meta_title"
                                       class="kt-input @error('meta_title') kt-input-invalid @enderror"
                                       value="{{ old('meta_title', $blogPost->meta_title) }}">
                                @error('meta_title')
                                <div class="text-xs text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-2 lg:col-span-2">
                                <label class="kt-form-label font-normal text-mono">Meta Description</label>
                                <input name="meta_description"
                                       class="kt-input @error('meta_description') kt-input-invalid @enderror"
                                       value="{{ old('meta_description', $blogPost->meta_description) }}">
                                @error('meta_description')
                                <div class="text-xs text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-2 lg:col-span-3">
                                <label class="kt-form-label font-normal text-mono">Meta Keywords</label>
                                <input name="meta_keywords"
                                       class="kt-input @error('meta_keywords') kt-input-invalid @enderror"
                                       value="{{ old('meta_keywords', $blogPost->meta_keywords) }}">
                                @error('meta_keywords')
                                <div class="text-xs text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    {{-- Right --}}
                    <div class="lg:col-span-1 flex flex-col gap-6">

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Kategoriler</label>

                            <select name="category_ids[]" multiple
                                    class="hidden"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Kategoriler"
                                    data-kt-select-multiple="true"
                                    data-kt-select-tags="true"
                                    data-kt-select-config='{"showSelectedCount":true,"enableSelectAll":true,"selectAllText":"Tümünü Seç","clearAllText":"Tümünü Temizle"}'>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(in_array($c->id, old('category_ids', $selectedCategoryIds)))>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Gallery (shared component) --}}
                        @include('admin.components.gallery-manager', [
                          'id' => 'blog-' . $blogPost->id,
                          'title' => 'Galeriler',
                          'routes' => [
                            'list' => route('admin.galleries.list'),
                            'index' => route('admin.blog.galleries.index', $blogPost),
                            'attach' => route('admin.blog.galleries.attach', $blogPost),
                            'detach' => route('admin.blog.galleries.detach', $blogPost),
                            'reorder' => route('admin.blog.galleries.reorder', $blogPost),
                          ],
                          'slots' => [
                            'main' => 'Ana',
                            'sidebar' => 'Sidebar',
                          ],
                        ])

                        {{-- Featured image (component) --}}
                        @include('admin.components.featured-image-manager', [
                            'name' => 'featured_image',
                            'mediaIdName' => 'featured_media_id',
                            'currentMediaId' => $blogPost->featuredMediaOne()?->id,
                            'currentUrl' => $blogPost->featuredMediaUrl() ?? $blogPost->featured_image_url,
                        ])

                        {{-- Publish --}}
                        <div class="flex items-center justify-between border border-border rounded-md p-4">
                            <div class="flex flex-col">
                                <span class="font-medium">Yayın Durumu</span>
                                <span class="text-sm text-muted-foreground">{{ $blogPost->is_published ? 'Yayında' : 'Taslak' }}</span>
                            </div>

                            <label class="kt-switch">
                                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $blogPost->is_published))>
                                <span class="kt-switch-slider"></span>
                            </label>
                        </div>

                    </div>

                </div>
            </form>
        </div>

        @include('admin.pages.media.partials._upload-modal')
    </div>
@endsection
