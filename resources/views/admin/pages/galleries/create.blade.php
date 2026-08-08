@extends('admin.layouts.main.app')

@section('content')
    <div class="kt-container-fixed" data-page="galleries.create">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-semibold">Yeni Galeri</h1>
                    <div class="text-sm text-muted-foreground">İsim ve açıklama oluştur.</div>
                </div>

                <a href="{{ route('admin.galleries.index') }}" class="kt-btn kt-btn-light">
                    <i class="ki-outline ki-left"></i> Geri
                </a>
            </div>

            <div class="kt-card">
                <form class="kt-card-content p-7 grid gap-5"
                      method="POST"
                      action="{{ route('admin.galleries.store') }}">
                    @csrf

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">İsim</label>
                        <input type="text"
                               name="name"
                               class="kt-input"
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                        <div class="text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Kısa Bağlantı (opsiyonel)</label>
                        <input type="text"
                               name="slug"
                               class="kt-input"
                               value="{{ old('slug') }}">
                        @error('slug')
                        <div class="text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Açıklama</label>
                        <textarea name="description"
                                  class="kt-textarea"
                                  rows="4">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="flex items-start justify-between gap-4 rounded-2xl border border-border bg-muted/30 p-4">
                        <span>
                            <span class="block text-sm font-semibold text-foreground">Site galerisinde yayınla</span>
                            <span class="mt-1 block text-xs leading-5 text-muted-foreground">Açıldığında bu galeri ve içindeki görseller herkese açık galeri sayfasında görünür.</span>
                        </span>
                        <input type="hidden" name="is_public" value="0">
                        <input type="checkbox" name="is_public" value="1" class="kt-switch" @checked(old('is_public'))>
                    </label>
                    @error('is_public')<div class="text-sm text-destructive">{{ $message }}</div>@enderror

                    <div class="flex items-center justify-end gap-2">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-outline ki-check"></i> Kaydet
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
