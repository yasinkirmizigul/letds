@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6" data-page="permissions.create">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Yeni Yetki</h1>
            <a class="kt-btn kt-btn-light" href="{{ route('admin.permissions.index') }}">Geri</a>
        </div>

        <div class="kt-card max-w-2xl w-full">
            <form class="kt-card-content flex flex-col gap-5 p-8"
                  method="POST"
                  action="{{ route('admin.permissions.store') }}">
                @csrf

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Yetki Adı</label>
                    <input class="kt-input" name="name" value="{{ old('name') }}" placeholder="Blog Görüntüleme"
                           required/>
                    @error('name')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Slug (örn: blog.view)</label>
                    <input class="kt-input" name="slug" value="{{ old('slug') }}" placeholder="blog.view" required/>
                    @error('slug')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                    <div class="text-xs text-muted-foreground mt-1">Tavsiye: resource.action formatı kullan (blog.view,
                        users.manage, pricing.view)
                    </div>
                </div>

                <div class="flex gap-2">
                    <button class="kt-btn kt-btn-primary" type="submit">Kaydet</button>
                    <a class="kt-btn kt-btn-light" href="{{ route('admin.permissions.index') }}">İptal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
