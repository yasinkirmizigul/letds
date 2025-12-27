@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6"
         data-page="galleries.edit"
         data-gallery-id="{{ $gallery->id }}">

        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Galeri Düzenle</h1>
                <div class="text-sm text-muted-foreground">ID: {{ $gallery->id }} • Slug: {{ $gallery->slug }}</div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.galleries.index') }}" class="kt-btn kt-btn-light">Geri</a>
                <button class="kt-btn kt-btn-info" type="button" data-kt-modal-toggle="#mediaUploadModal">
                    <i class="ki-outline ki-image"></i> Medya Ekle
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 flex flex-col gap-6">

                <div class="kt-card">
                    <form class="kt-card-content p-8 flex flex-col gap-6"
                          method="POST"
                          action="{{ route('admin.galleries.update', ['gallery' => $gallery->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="kt-form-label font-normal text-mono">İsim</label>
                                <input name="name" class="kt-input @error('name') kt-input-invalid @enderror" value="{{ old('name', $gallery->name) }}">
                                @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="kt-form-label font-normal text-mono">Slug</label>
                                <input name="slug" class="kt-input @error('slug') kt-input-invalid @enderror" value="{{ old('slug', $gallery->slug) }}">
                                @error('slug') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                            </div>

                            <div class="lg:col-span-2 flex flex-col gap-2">
                                <label class="kt-form-label font-normal text-mono">Açıklama</label>
                                <textarea name="description" rows="4" class="kt-textarea @error('description') kt-input-invalid @enderror">{{ old('description', $gallery->description) }}</textarea>
                                @error('description') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button class="kt-btn kt-btn-primary" type="submit">Güncelle</button>
                        </div>
                    </form>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header py-5 flex-wrap gap-4">
                        <div class="flex flex-col">
                            <h3 class="kt-card-title">Galeri Öğeleri</h3>
                            <div class="text-sm text-muted-foreground">Drag-drop ile sırala, caption/alt/link override gir.</div>
                        </div>
                    </div>

                    <div class="kt-card-content p-6">
                        <div id="galleryItemsEmpty" class="text-sm text-muted-foreground hidden">Henüz medya eklenmedi.</div>
                        <div id="galleryItemsList" class="flex flex-col gap-3"></div>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-1 flex flex-col gap-6">
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title">Silme</h3>
                    </div>
                    <div class="kt-card-content p-6">
                        <form method="POST" action="{{ route('admin.galleries.destroy', ['gallery' => $gallery->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="kt-btn kt-btn-danger w-full" type="submit">Çöpe Taşı</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Media modal --}}
        @include('admin.pages.media.partials._upload-modal')

        {{-- Picker footer button --}}
        <div class="hidden" id="mediaPickerHook"></div>
    </div>
@endsection
