@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6" data-page="galleries.create">
        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Yeni Galeri</h1>
                <div class="text-sm text-muted-foreground">İsim ve açıklama oluştur.</div>
            </div>
            <a href="{{ route('admin.galleries.index') }}" class="kt-btn kt-btn-light">Geri</a>
        </div>

        <div class="kt-card">
            <form class="kt-card-content p-8 flex flex-col gap-6"
                  method="POST"
                  action="{{ route('admin.galleries.store') }}">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="kt-form-label font-normal text-mono">İsim</label>
                        <input name="name" class="kt-input @error('name') kt-input-invalid @enderror" value="{{ old('name') }}">
                        @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="kt-form-label font-normal text-mono">Slug (opsiyonel)</label>
                        <input name="slug" class="kt-input @error('slug') kt-input-invalid @enderror" value="{{ old('slug') }}">
                        @error('slug') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="lg:col-span-2 flex flex-col gap-2">
                        <label class="kt-form-label font-normal text-mono">Açıklama</label>
                        <textarea name="description" rows="4" class="kt-textarea @error('description') kt-input-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button class="kt-btn kt-btn-primary" type="submit">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
@endsection
