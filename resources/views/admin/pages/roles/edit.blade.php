@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6">
        @include('admin.partials._flash')

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Rol Düzenle</h1>
            <a class="kt-btn kt-btn-light" href="{{ route('admin.roles.index') }}">Geri</a>
        </div>

        <div class="kt-card max-w-3xl w-full">
            <form class="kt-card-content flex flex-col gap-6 p-8"
                  method="POST"
                  action="{{ route('admin.roles.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Rol Adı</label>
                    <input class="kt-input" name="name" value="{{ old('name', $role->name) }}" required/>
                    @error('name')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Slug</label>
                    <input class="kt-input" name="slug" value="{{ old('slug', $role->slug) }}" required/>
                    @error('slug')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                </div>

                @php
                    $selectedPerms = old('permissions', $role->permissions->pluck('id')->all());
                @endphp

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div class="kt-form-label font-normal text-mono">Yetkiler</div>
                        <div class="flex gap-2">
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light" id="perm_select_all">Tümünü
                                seç
                            </button>
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light" id="perm_clear_all">Temizle
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($permissions as $perm)
                            <label class="kt-label">
                                <input
                                    class="kt-checkbox kt-checkbox-sm perm-check"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $perm->id }}"
                                    @checked(in_array($perm->id, $selectedPerms))
                                />
                                <span class="kt-checkbox-label">
                                <span class="font-medium">{{ $perm->slug }}</span>
                                <span class="text-xs text-muted-foreground block">{{ $perm->name }}</span>
                            </span>
                            </label>
                        @endforeach
                    </div>

                    @error('permissions')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="flex gap-2">
                    <button class="kt-btn kt-btn-primary" type="submit">Güncelle</button>
                    <a class="kt-btn kt-btn-light" href="{{ route('admin.roles.index') }}">İptal</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const allBtn = document.getElementById('perm_select_all');
                const clearBtn = document.getElementById('perm_clear_all');
                const checks = () => Array.from(document.querySelectorAll('.perm-check'));

                if (allBtn) allBtn.addEventListener('click', () => checks().forEach(c => c.checked = true));
                if (clearBtn) clearBtn.addEventListener('click', () => checks().forEach(c => c.checked = false));
            });
        </script>
    @endpush
@endsection
