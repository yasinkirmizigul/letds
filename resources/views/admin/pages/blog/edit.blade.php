@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6"
         data-page="blog.edit"
         data-blog-id="{{ $blogPost->id }}"
         data-upload-url="{{ route('admin.tinymce.upload') }}"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Blog Düzenle</h1>
                <div class="text-sm text-muted-foreground">ID: {{ $blogPost->id }} • Slug: {{ $blogPost->slug }}</div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">Geri</a>

                <button form="blog-update-form" class="kt-btn kt-btn-primary" type="submit">
                    Güncelle
                </button>

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

            {{-- UPDATE FORM (buttons are outside and linked via form="...") --}}
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
                                <button type="button" id="slugifyBtn" class="kt-btn kt-btn-light">Oluştur</button>
                            </div>
                            @error('slug')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
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

                        {{-- Categories --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Kategoriler</label>

                            <select name="category_ids[]" multiple
                                    class="kt-select @error('category_ids') kt-input-invalid @enderror"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Kategori Listesi">
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(in_array($c->id, old('category_ids', $selectedCategoryIds)))>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('category_ids')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Galleries --}}
                        <div class="kt-card">
                            <div class="kt-card-header py-4">
                                <h3 class="kt-card-title">Galeriler</h3>
                                <div class="kt-card-toolbar flex items-center gap-2">
                                    <button type="button"
                                            class="kt-btn kt-btn-sm kt-btn-light"
                                            id="blogGalleryAttachBtn"
                                            data-kt-modal-target="#blogGalleryPickerModal">
                                        <i class="ki-outline ki-plus"></i> Ekle
                                    </button>
                                </div>
                            </div>

                            <div class="kt-card-content p-4 grid gap-4">
                                <div class="text-xs text-muted-foreground">
                                    Blog’a galeri bağla, slot seç, sırala.
                                </div>

                                <div id="blogGalleriesEmpty" class="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground">
                                    Henüz galeri bağlı değil.
                                </div>

                                <div class="grid gap-3">
                                    <div class="rounded-xl border border-border bg-background">
                                        <button type="button"
                                                class="w-full px-4 py-3 flex items-center justify-between"
                                                data-acc="main">
                                            <div class="font-medium">Main</div>
                                            <span class="kt-badge kt-badge-outline">Sürükle-bırak</span>
                                        </button>
                                        <div class="px-4 pb-4 pt-2">
                                            <div id="blogGalleriesMain" class="grid gap-2"></div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-border bg-background">
                                        <button type="button"
                                                class="w-full px-4 py-3 flex items-center justify-between"
                                                data-acc="sidebar">
                                            <div class="font-medium">Sidebar</div>
                                            <span class="kt-badge kt-badge-outline">Sürükle-bırak</span>
                                        </button>
                                        <div class="px-4 pb-4 pt-2">
                                            <div id="blogGalleriesSidebar" class="grid gap-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Gallery picker modal --}}
                        <div class="kt-modal kt-modal-center hidden" id="blogGalleryPickerModal">
                            <div class="kt-modal-content max-w-[900px]" style="max-height: 92vh">
                                <div class="kt-modal-header">
                                    <h3 class="kt-modal-title">Galeri Seç</h3>
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-close="true">
                                        <i class="ki-outline ki-cross"></i>
                                    </button>
                                </div>

                                <div class="kt-modal-body p-6 overflow-auto">
                                    <div class="grid gap-4">
                                        <div class="flex flex-col md:flex-row md:items-center gap-3">
                                            <div class="grow">
                                                <input id="blogGalleryPickerSearch"
                                                       type="text"
                                                       class="kt-input"
                                                       placeholder="Galeride ara (isim/slug)"/>
                                            </div>

                                            <div class="w-full md:w-56">
                                                <select id="blogGalleryPickerSlot" class="kt-select w-full">
                                                    <option value="main">Main</option>
                                                    <option value="sidebar">Sidebar</option>
                                                </select>
                                            </div>

                                            <button type="button" id="blogGalleryPickerRefresh" class="kt-btn kt-btn-light">
                                                <i class="ki-outline ki-arrows-circle"></i> Yenile
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                                            <div id="blogGalleryPickerInfo">0-0 / 0</div>
                                            <div id="blogGalleryPickerPagination" class="flex items-center gap-1"></div>
                                        </div>

                                        <div id="blogGalleryPickerEmpty"
                                             class="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground">
                                            Kayıt yok.
                                        </div>

                                        <div id="blogGalleryPickerList" class="grid gap-2"></div>
                                    </div>
                                </div>

                                <div class="kt-modal-footer justify-end gap-2">
                                    <button type="button" class="kt-btn kt-btn-light" data-kt-modal-close="true">Kapat</button>
                                </div>
                            </div>
                        </div>

                        {{-- Featured image --}}
                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Öne Çıkan Görsel</label>

                            <input id="featured_image"
                                   type="file"
                                   name="featured_image"
                                   accept="image/*"
                                   class="kt-input @error('featured_image') kt-input-invalid @enderror">

                            @error('featured_image')
                            <div class="text-xs text-danger">{{ $message }}</div>
                            @enderror

                            @if($blogPost->featuredImageUrl())
                                <img id="featuredPreview"
                                     src="{{ $blogPost->featuredImageUrl() }}"
                                     class="rounded-md border border-border max-h-52 object-cover">
                            @else
                                <img id="featuredPreview" class="hidden rounded-md border border-border max-h-52 object-cover">
                            @endif
                        </div>

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

        {{-- Gallery picker modal --}}
        <div class="kt-modal kt-modal-center" id="blogGalleryPickerModal" data-kt-modal="true">
            <div class="kt-modal-content max-w-[60%]" style="max-height: 90vh">
                <div class="kt-modal-header">
                    <h3 class="kt-modal-title">Galeri Seç</h3>
                    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-modal-dismiss="true">
                        <i class="ki-outline ki-cross"></i>
                    </button>
                </div>

                <div class="kt-modal-body overflow-hidden p-7">
                    <div class="flex items-center gap-3 mb-4">
                        <input class="kt-input w-80" id="blogGalleryPickerSearch" placeholder="Ara: isim / slug">
                        <select class="kt-select w-44" id="blogGalleryPickerSlot" data-kt-select="true" data-kt-select-placeholder="Seç">
                            <option value="main">Main</option>
                            <option value="sidebar">Sidebar</option>
                        </select>
                        <button class="kt-btn kt-btn-light" id="blogGalleryPickerRefresh">Yenile</button>
                    </div>

                    <div id="blogGalleryPickerEmpty" class="text-sm text-muted-foreground hidden">Kayıt yok.</div>
                    <div id="blogGalleryPickerList" class="flex flex-col gap-2"></div>

                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium mt-6">
                        <span id="blogGalleryPickerInfo"></span>
                        <div class="kt-datatable-pagination" id="blogGalleryPickerPagination"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Media modal (gallery edit gibi ileride blog içinden item bakmak istersen hazır kalsın) --}}
        @include('admin.pages.media.partials._upload-modal')

    </div>
@endsection
