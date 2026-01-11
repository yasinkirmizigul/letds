@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed"
         data-page="galleries.edit"
         data-gallery-id="{{ $gallery->id }}">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold">Galeri Düzenle</h1>
                    <div class="text-sm text-muted-foreground">
                        ID: {{ $gallery->id }} • Slug: {{ $gallery->slug }}
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.galleries.index') }}" class="kt-btn kt-btn-light">
                        <i class="ki-outline ki-left"></i> Geri
                    </a>

                    <button type="button" class="kt-btn kt-btn-primary" id="galleryAddMediaBtn"
                            onclick="document.querySelector('#mediaTabLibrary')?.click(); document.querySelector('[data-kt-modal-toggle=&quot;#mediaUploadModal&quot;]')?.click();">
                        <i class="ki-outline ki-folder"></i> Medya Ekle
                    </button>
                </div>
            </div>

            <div class="kt-card">
                <form class="kt-card-content p-7 grid gap-5"
                      method="POST"
                      action="{{ route('admin.galleries.update', $gallery) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">İsim</label>
                        <input type="text"
                               name="name"
                               class="kt-input"
                               value="{{ old('name', $gallery->name) }}"
                               required>
                        @error('name')
                        <div class="text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Slug</label>
                        <input type="text"
                               name="slug"
                               class="kt-input"
                               value="{{ old('slug', $gallery->slug) }}">
                        @error('slug')
                        <div class="text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Açıklama</label>
                        <textarea name="description"
                                  class="kt-textarea"
                                  rows="4">{{ old('description', $gallery->description) }}</textarea>
                        @error('description')
                        <div class="text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-outline ki-check"></i> Güncelle
                        </button>
                    </div>
                </form>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-4">
                    <div class="flex items-center gap-3">
                        <h3 class="kt-card-title">Galeri Öğeleri</h3>

                        {{-- Global status / dirty count --}}
                        <div class="text-xs text-muted-foreground flex items-center gap-2">
                            <span id="galleryDirtyCount"></span>
                            <span id="gallerySaveAllStatus" class="hidden"></span>
                        </div>
                    </div>

                    <div class="kt-card-toolbar flex items-center gap-2 flex-wrap">
                        <button type="button"
                                class="kt-btn kt-btn-sm kt-btn-primary"
                                id="gallerySaveAllBtn"
                                disabled>
                            <i class="ki-outline ki-save-2"></i> Toplu Kaydet
                        </button>

                        <button type="button"
                                class="kt-btn kt-btn-sm kt-btn-light"
                                data-kt-modal-toggle="#mediaUploadModal"
                                data-library-attach="true"
                                data-library-attach-url="{{ url('/admin/galleries/'.$gallery->id.'/items') }}"
                                data-library-attach-payload="media_ids"
                        >
                            Medya’dan Ekle
                        </button>
                    </div>
                </div>

                <div class="kt-card-content p-5 grid gap-3">
                    <div class="text-sm text-muted-foreground">
                        Drag-drop ile sırala, caption/alt/link override gir.
                    </div>

                    <div id="galleryItemsEmpty" class="text-sm text-muted-foreground">
                        Henüz medya eklenmedi.
                    </div>

                    <div id="galleryItemsList" class="grid gap-3">
                        {{-- JS basar --}}
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header py-4">
                    <h3 class="kt-card-title">Silme</h3>
                </div>

                <div class="kt-card-content p-5">
                    <form method="POST" action="{{ route('admin.galleries.destroy', $gallery) }}"
                          onsubmit="return confirm('Galeri çöp kutusuna taşınacak. Emin misin?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="kt-btn kt-btn-danger">
                            <i class="ki-outline ki-trash"></i> Çöpe Taşı
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Media modal --}}
    @include('admin.pages.media.partials._upload-modal')
@endsection
