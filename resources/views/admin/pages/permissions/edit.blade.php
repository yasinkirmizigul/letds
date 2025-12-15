@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Yetki Düzenle</h1>
            <a class="kt-btn kt-btn-light" href="{{ route('admin.permissions.index') }}">Geri</a>
        </div>

        <div class="kt-card max-w-2xl w-full">
            <form class="kt-card-content flex flex-col gap-5 p-8"
                  method="POST"
                  action="{{ route('admin.permissions.update', $permission) }}">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Yetki Adı</label>
                    <input class="kt-input" name="name" value="{{ old('name', $permission->name) }}" required/>
                    @error('name')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Slug</label>
                    <input class="kt-input" name="slug" value="{{ old('slug', $permission->slug) }}" required/>
                    @error('slug')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex gap-2">
                    <button class="kt-btn kt-btn-primary" type="submit">Güncelle</button>
                    <a class="kt-btn kt-btn-light" href="{{ route('admin.permissions.index') }}">İptal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
