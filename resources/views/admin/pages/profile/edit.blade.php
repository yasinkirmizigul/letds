@extends('admin.layouts.main.app')

@section('content')
    @php
        $u = $user ?? auth()->user();

        // Controller'dan geliyorsa onu kullan, gelmiyorsa güvenli fallback
        $blankAvatarUrl = $blankAvatarUrl ?? asset('assets/media/blank.png');

        // Eğer modelde avatarUrl() gibi bir helper varsa onu kullan
        if ($u && method_exists($u, 'avatarUrl')) {
            $avatarUrl = $u->avatarUrl();
        } else {
            // Son çare: boş bırak (preview yine placeholder ile çalışır)
            $avatarUrl = $avatarUrl ?? '';
        }
    @endphp

    <div class="kt-container-fixed" data-page="profile.edit">
        <div class="grid gap-5 lg:gap-7.5">

            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Profil</h1>
                    <div class="text-sm text-muted-foreground">Profil bilgilerini ve avatarını düzenle</div>
                </div>

                <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-light">Geri</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-7.5">

                {{-- LEFT: Avatar --}}
                <div class="lg:col-span-4">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title text-lg font-semibold">Avatar</h3>
                        </div>

                        <div class="kt-card-content p-6 flex flex-col gap-5">

                            {{-- Avatar Update (Media picker ile avatar_media_id set edilecek) --}}
                            <form method="POST"
                                  action="{{ route('admin.profile.update') }}"
                                  class="flex flex-col gap-4"
                                  enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <input type="hidden"
                                       name="avatar_media_id"
                                       id="avatarMediaId"
                                       value="{{ old('avatar_media_id', $u->avatar_media_id ?? null) }}">

                                {{-- KTUI Image Input (Tailwind) --}}
                                <div class="flex items-center gap-4">
                                    <div class="kt-image-input"
                                         data-kt-image-input="true"
                                         id="profileAvatarInput">

                                        {{-- İstersen dosyadan seçmeyi de destekle (opsiyonel) --}}
                                        <input type="file" accept=".png,.jpg,.jpeg,.webp" name="avatar_file" />

                                        {{-- KTUI remove state --}}
                                        <input type="hidden" name="avatar_remove" value="0" />

                                        {{-- Remove / revert --}}
                                        <button type="button"
                                                data-kt-image-input-remove="true"
                                                class="kt-image-input-remove"
                                                title="Kaldır / geri al">
                                            <i class="ki-filled ki-cross"></i>
                                        </button>

                                        {{-- Placeholder + Preview (KTUI zorunlu yapı) --}}
                                        <div data-kt-image-input-placeholder="true"
                                             class="kt-image-input-placeholder"
                                             style="background-image: url('{{ $blankAvatarUrl }}')">

                                            <div id="avatarPreview"
                                                 data-kt-image-input-preview="true"
                                                 class="kt-image-input-preview"
                                                 @if(!empty($avatarUrl))
                                                     style="background-image: url('{{ $avatarUrl }}')"
                                                @endif>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <button type="button"
                                                class="kt-btn kt-btn-light"
                                                data-media-picker="true"
                                                data-media-picker-target="#avatarMediaId"
                                                data-media-picker-preview="#avatarPreview"
                                                data-media-picker-mime="image/">
                                            Medyadan Seç
                                        </button>

                                        <button type="submit" class="kt-btn kt-btn-primary">
                                            Kaydet
                                        </button>
                                    </div>
                                </div>

                                @error('avatar_media_id')
                                <div class="text-xs text-danger">{{ $message }}</div>
                                @enderror

                                @error('avatar_file')
                                <div class="text-xs text-danger">{{ $message }}</div>
                                @enderror

                                @error('avatar_remove')
                                <div class="text-xs text-danger">{{ $message }}</div>
                                @enderror

                                <div class="text-xs text-muted-foreground">
                                    Not: Media’dan seçince <span class="font-medium">avatar_media_id</span> set edilir. Kaydet’e basmayı unutma.
                                </div>
                            </form>

                            {{-- Hard remove (DB null + purgeUserAvatarMedia) --}}
                            <form method="POST" action="{{ route('admin.profile.avatar.remove') }}">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="kt-btn kt-btn-danger kt-btn-sm">
                                    Avatarı Kaldır
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Profile info --}}
                <div class="lg:col-span-8">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title text-lg font-semibold">Bilgiler</h3>
                        </div>

                        <form method="POST"
                              action="{{ route('admin.profile.update') }}"
                              class="kt-card-content p-6 flex flex-col gap-5">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label">İsim</label>
                                    <input class="kt-input @error('name') kt-input-invalid @enderror"
                                           name="name"
                                           value="{{ old('name', $u->name ?? '') }}">
                                    @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label">Email</label>
                                    <input class="kt-input @error('email') kt-input-invalid @enderror"
                                           name="email"
                                           value="{{ old('email', $u->email ?? '') }}">
                                    @error('email') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="kt-badge kt-badge-sm {{ !empty($u->email_verified_at) ? 'kt-badge-success' : 'kt-badge-warning' }}">
                                    {{ !empty($u->email_verified_at) ? 'Email doğrulandı' : 'Email doğrulanmadı' }}
                                </span>

                                <span class="kt-badge kt-badge-sm {{ !empty($u->is_active) ? 'kt-badge-success' : 'kt-badge-danger' }}">
                                    {{ !empty($u->is_active) ? 'Aktif' : 'Pasif' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-light">İptal</a>
                                <button type="submit" class="kt-btn kt-btn-primary">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
