@extends('admin.layouts.main.app')

@section('content')
    @php($u = $user ?? auth()->user())
    @php($avatarUrl = method_exists($u, 'avatarUrl') ? $u->avatarUrl() : asset('assets/media/blank.png'))

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

                        <div class="kt-card-content p-6 flex flex-col gap-4">

                            {{-- Avatar update --}}
                            <form method="POST" action="{{ route('admin.profile.update') }}" class="flex flex-col gap-4" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <input type="hidden"
                                       name="avatar_media_id"
                                       id="avatarMediaId"
                                       value="{{ old('avatar_media_id', $u->avatar_media_id) }}">

                                <div class="flex items-center gap-4">
                                    <div class="kt-image-input" data-kt-image-input="true">
                                        {{-- KTUI zorunlu preview --}}
                                        <div id="avatarPreview"
                                             data-kt-image-input-preview="true"
                                             class="kt-image-input-preview w-20 h-20 rounded-full border border-border bg-cover bg-center"
                                             style="background-image:url('{{ $avatarUrl }}')">
                                        </div>

                                        {{-- Change (dosyadan seçmek istersen) --}}
                                        <label class="kt-btn kt-btn-icon kt-btn-circle kt-btn-xs kt-btn-primary"
                                               data-kt-image-input-action="change"
                                               title="Dosyadan seç">
                                            <i class="ki-filled ki-pencil"></i>
                                            <input type="file" name="avatar_file" accept=".png,.jpg,.jpeg,.webp">
                                            <input type="hidden" name="avatar_remove" value="0">
                                        </label>

                                        {{-- Cancel --}}
                                        <span class="kt-btn kt-btn-icon kt-btn-circle kt-btn-xs kt-btn-light"
                                              data-kt-image-input-action="cancel"
                                              title="İptal">
                                        <i class="ki-filled ki-cross"></i>
                                    </span>

                                        {{-- Remove (sadece UI temizler; kaydetmen gerekir) --}}
                                        <span class="kt-btn kt-btn-icon kt-btn-circle kt-btn-xs kt-btn-danger"
                                              data-kt-image-input-action="remove"
                                              title="Kaldır">
                                        <i class="ki-filled ki-trash"></i>
                                    </span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
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
                            </form>

                            {{-- Hard remove (DB null + media cleanup) --}}
                            <form method="POST" action="{{ route('admin.profile.avatar.remove') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kt-btn kt-btn-danger kt-btn-sm">Avatarı Kaldır</button>
                            </form>

                            <div class="text-xs text-muted-foreground">
                                Not: Media’dan seçince avatar_media_id set edilir. Kaydet’e basmayı unutma.
                            </div>

                        </div>
                    </div>
                </div>

                {{-- RIGHT: Profile form --}}
                <div class="lg:col-span-8">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title text-lg font-semibold">Bilgiler</h3>
                        </div>

                        <form method="POST" action="{{ route('admin.profile.update') }}" class="kt-card-content p-6 flex flex-col gap-4">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="kt-form-label">İsim</label>
                                    <input class="kt-input @error('name') kt-input-invalid @enderror"
                                           name="name"
                                           value="{{ old('name', $u->name) }}">
                                    @error('name') <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="kt-form-label">Email</label>
                                    <input class="kt-input @error('email') kt-input-invalid @enderror"
                                           name="email"
                                           value="{{ old('email', $u->email) }}">
                                    @error('email') <div class="text-xs text-danger mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                            <span class="kt-badge kt-badge-sm {{ $u->email_verified_at ? 'kt-badge-success' : 'kt-badge-warning' }}">
                                {{ $u->email_verified_at ? 'Email doğrulandı' : 'Email doğrulanmadı' }}
                            </span>
                                <span class="kt-badge kt-badge-sm {{ $u->is_active ? 'kt-badge-success' : 'kt-badge-danger' }}">
                                {{ $u->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-light">İptal</a>
                                <button class="kt-btn kt-btn-primary" type="submit">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
