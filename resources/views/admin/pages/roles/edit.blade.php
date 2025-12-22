@extends('admin.layouts.main.app')

@section('content')
    <div class="px-4 lg:px-6" data-page="roles.edit">
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

                {{-- ✅ Priority --}}
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">Priority</label>
                    <input class="kt-input"
                           type="number"
                           name="priority"
                           min="0"
                           step="1"
                           value="{{ old('priority', $role->priority ?? 0) }}"
                           placeholder="Örn: 900"/>
                    <div class="text-xs text-muted-foreground">
                        Büyük sayı = daha yüksek öncelik. Örn: superadmin 1000, admin 900, editor 100.
                    </div>
                    @error('priority')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                </div>

                @php
                    $selectedPerms = old('permissions', $role->permissions->pluck('id')->all());
                @endphp

                {{-- $selectedPerms aynı kalsın --}}

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div class="kt-form-label font-normal text-mono">Yetkiler</div>
                        <div class="flex gap-2">
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light" id="perm_select_all">Tümünü seç</button>
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-light" id="perm_clear_all">Temizle</button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-5">
                        @foreach($permissions as $group => $perms)
                            <div class="rounded-xl border border-input p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="font-semibold text-sm capitalize">{{ $group }}</div>
                                    <div class="flex gap-2">
                                        <button type="button"
                                                class="kt-btn kt-btn-xs kt-btn-light"
                                                data-perm-group-select="{{ $group }}">
                                            Hepsi
                                        </button>
                                        <button type="button"
                                                class="kt-btn kt-btn-xs kt-btn-light"
                                                data-perm-group-clear="{{ $group }}">
                                            Temizle
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" data-perm-group="{{ $group }}">
                                    @foreach($perms as $perm)
                                        <label class="kt-label">
                                            <input
                                                class="kt-checkbox kt-checkbox-sm perm-check"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $perm->id }}"
                                                @checked(in_array($perm->id, $selectedPerms, true))
                                            />
                                            <span class="kt-checkbox-label">
                                                <span class="font-medium">{{ $perm->slug }}</span>
                                                <span class="text-xs text-muted-foreground block">{{ $perm->name }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('permissions')
                    <div class="text-xs text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <button class="kt-btn kt-btn-primary" type="submit">Güncelle</button>
                    <a class="kt-btn kt-btn-mono" href="{{ route('admin.roles.index') }}">İptal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
