@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Yeni Kullanıcı</h1>
            <a class="kt-btn kt-btn-light" href="{{ route('admin.users.index') }}">Geri</a>
        </div>

        <div class="kt-card max-w-2xl">
            <form class="kt-card-content flex flex-col gap-5 p-8" method="POST"
                  action="{{ route('admin.users.store') }}">
                @csrf

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Ad</label>
                    <input class="kt-input" name="name" value="{{ old('name') }}" required/>
                    @error('name')
                    <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Email</label>
                    <input class="kt-input" name="email" value="{{ old('email') }}" required/>
                    @error('email')
                    <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Şifre</label>
                    <input class="kt-input" name="password" type="password" required/>
                    @error('password')
                    <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>

                <label class="kt-label">
                    <input class="kt-checkbox kt-checkbox-sm" name="is_active" type="checkbox"
                           value="1" @checked(old('is_active', true)) />
                    <span class="kt-checkbox-label">Aktif</span>
                </label>

                <div class="flex flex-col gap-2">
                    <div class="kt-form-label font-normal text-mono">Roller</div>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($roles as $role)
                            <label class="kt-label">
                                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" name="roles[]"
                                       value="{{ $role->id }}" @checked(in_array($role->id, old('roles', []))) />
                                <span class="kt-checkbox-label">{{ $role->name }} ({{ $role->slug }})</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                    <div class="text-xs text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="flex gap-2">
                    <button class="kt-btn kt-btn-primary" type="submit">Kaydet</button>
                    <a class="kt-btn kt-btn-light" href="{{ route('admin.users.index') }}">İptal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
