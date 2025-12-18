@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6"
         data-page="blog.create"
         data-upload-url="{{ route('admin.tinymce.upload') }}"
         data-tinymce-src="{{ asset('assets/vendors/tinymce/tinymce.min.js') }}"
         data-tinymce-base="{{ asset('assets/vendors/tinymce') }}"
         data-tinymce-lang-url="{{ asset('assets/vendors/tinymce/langs/tr.js') }}">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Yeni Blog Yazısı</h1>
                <div class="text-sm text-muted-foreground">Başlık, içerik, SEO ve kategori</div>
            </div>

            <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">Geri</a>
        </div>

        <div class="kt-card">
            <form class="kt-card-content p-8 flex flex-col gap-6"
                  method="POST"
                  action="{{ route('admin.blog.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Left --}}
                    <div class="lg:col-span-2 flex flex-col gap-6">

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Başlık</label>
                            <input class="kt-input @error('title') kt-input-invalid @enderror"
                                   name="title" value="{{ old('title') }}" placeholder="Başlık" required/>
                            @error('title')
                            <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Slug</label>
                            <input id="slug"
                                   class="kt-input @error('slug') kt-input-invalid @enderror"
                                   name="slug"
                                   value="{{ old('slug') }}"
                                   placeholder="otomatik oluşur (istersen değiştir)"/>

                            @error('slug')
                            <div class="text-xs text-danger">{{ $message }}</div> @enderror

                            <div class="text-sm text-muted-foreground">
                                URL Önizleme:
                                <span class="font-medium">{{ url('/blog') }}/<span
                                        id="url_slug_preview">{{ old('slug') }}</span></span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">İçerik</label>
                            <textarea id="content_editor" name="content" class="kt-input min-h-[320px]">
                                {{ old('content', $blogPost->content ?? '') }}
                            </textarea>
                            @error('content')
                            <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    {{-- Right --}}
                    <div class="lg:col-span-1 flex flex-col gap-6">

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Kategoriler</label>
                            <select name="category_ids[]" multiple
                                    class="kt-select @error('category_ids') kt-input-invalid @enderror"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Kategoriler...">
                                @foreach($categories as $cat)
                                    <option
                                        value="{{ $cat->id }}" @selected(collect(old('category_ids'))->contains($cat->id))>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_ids')
                            <div class="text-xs text-danger">{{ $message }}</div> @enderror
                            <div class="text-xs text-muted-foreground">Çoklu seçebilirsin. Ürün/galeri de aynı kategori
                                yapısını kullanacak.
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Anahtar Kelimeler</label>
                            <input class="kt-input @error('meta_keywords') kt-input-invalid @enderror"
                                   name="meta_keywords"
                                   value="{{ old('meta_keywords') }}"
                                   placeholder="örn: veri analizi, istatistik"/>
                            @error('meta_keywords')
                            <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Açıklama</label>
                            <textarea
                                class="kt-input min-h-[90px] @error('meta_description') kt-input-invalid @enderror"
                                name="meta_description"
                                maxlength="255"
                                placeholder="Google snippet için kısa açıklama...">{{ old('meta_description') }}</textarea>
                            @error('meta_description')
                            <div class="text-xs text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="kt-form-label font-normal text-mono">Öne Çıkan Görsel</label>

                            <input id="featured_image"
                                   type="file"
                                   name="featured_image"
                                   accept="image/*"
                                   class="kt-input @error('featured_image') kt-input-invalid @enderror">

                            @error('featured_image')
                            <div class="text-xs text-danger">{{ $message }}</div> @enderror

                            <div class="mt-2">
                                <div class="text-sm text-muted-foreground mb-2">Önizleme</div>

                                <div id="featured_placeholder" class="rounded-xl border bg-muted"
                                     style="width:100%; height:220px;"></div>

                                <img id="featured_preview"
                                     src=""
                                     alt=""
                                     class="hidden rounded-xl border"
                                     style="width:100%; height:220px; object-fit:cover;">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="font-medium">Yayınla</span>
                                <span class="text-sm text-muted-foreground">Açık olursa yayında</span>
                            </div>

                            <input type="checkbox"
                                   name="is_published"
                                   value="1"
                                   class="kt-switch kt-switch-mono"
                                @checked(old('is_published')) />
                        </div>

                        <div class="flex gap-2 justify-center">
                            <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
                            <a href="{{ route('admin.blog.index') }}" class="kt-btn kt-btn-light">İptal</a>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

