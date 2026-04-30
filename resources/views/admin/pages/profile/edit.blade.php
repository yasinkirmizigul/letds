@extends('admin.layouts.main.app')

@section('content')
    @php
        $u = $user ?? auth()->user();
        $blankAvatarUrl = $blankAvatarUrl ?? asset('assets/media/blank.png');
        $avatarUrl = $u && method_exists($u, 'avatarUrl') ? $u->avatarUrl() : $blankAvatarUrl;
        $skillsText = old('skills_text', implode(', ', method_exists($u, 'skillTags') ? $u->skillTags() : []));
        $roles = $u->roles ?? collect();
    @endphp

    <div class="kt-container-fixed" data-page="profile.edit">
        <div class="grid gap-5 lg:gap-7.5">
            @includeIf('admin.partials._flash')

            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-xl font-semibold text-mono">Profil Düzenle</h1>
                    <div class="text-sm text-muted-foreground">Kişisel profil bilgilerini, avatarını ve görünür uzmanlık alanlarını yönet.</div>
                </div>

                <a href="{{ route('admin.profile.index') }}" class="kt-btn kt-btn-light">
                    <i class="ki-filled ki-arrow-left"></i>
                    Profil Sayfasına Dön
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-7.5">
                <div class="lg:col-span-4">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <h3 class="kt-card-title text-lg font-semibold">Avatar</h3>
                        </div>

                        <div class="kt-card-content p-6 flex flex-col gap-5">
                            <form id="avatarForm"
                                  method="POST"
                                  action="{{ route('admin.profile.avatar') }}"
                                  class="flex flex-col gap-5"
                                  enctype="multipart/form-data">
                                @csrf

                                <input type="hidden"
                                       name="avatar_media_id"
                                       id="avatar_media_id"
                                       value="{{ old('avatar_media_id', $u->avatarMedia?->id) }}">

                                <div class="flex items-center gap-4">
                                    <div class="kt-image-input"
                                         data-kt-image-input="true"
                                         id="profileAvatarInput">

                                        <input type="file" accept=".png,.jpg,.jpeg,.webp" name="avatar_file">
                                        <input type="hidden" name="avatar_remove" value="0">

                                        <button type="button"
                                                data-kt-image-input-remove="true"
                                                class="kt-image-input-remove"
                                                title="Kaldır / geri al">
                                            <i class="ki-filled ki-cross"></i>
                                        </button>

                                        <div data-kt-image-input-placeholder="true"
                                             class="kt-image-input-placeholder"
                                             style="background-image: url('{{ $blankAvatarUrl }}')">
                                            <div id="avatarPreview"
                                                 data-kt-image-input-preview="true"
                                                 class="kt-image-input-preview"
                                                 style="background-image: url('{{ $avatarUrl }}')">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <button type="button"
                                                class="kt-btn kt-btn-light"
                                                data-media-picker="true"
                                                data-media-picker-target="#avatar_media_id"
                                                data-media-picker-preview="#avatarPreview"
                                                data-media-picker-mime="image/">
                                            Medyadan Seç
                                        </button>

                                        <button type="submit" class="kt-btn kt-btn-primary">
                                            Avatarı Kaydet
                                        </button>
                                    </div>
                                </div>

                                @error('avatar_media_id') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                @error('avatar_file') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                @error('avatar_remove') <div class="text-xs text-danger">{{ $message }}</div> @enderror

                                <div class="text-xs text-muted-foreground leading-5">
                                    JPG, PNG veya WebP formatı önerilir. En iyi görünüm için kare ve en az 300x300 px görsel kullan.
                                </div>
                            </form>

                            @if($u->avatar_media_id || $u->avatar)
                                <form method="POST" action="{{ route('admin.profile.avatar.remove') }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="kt-btn kt-btn-danger kt-btn-sm">
                                        Avatarı Kaldır
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="kt-card mt-5 lg:mt-7.5">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Hesap Özeti</h3>
                        </div>
                        <div class="kt-card-content flex flex-col gap-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-secondary-foreground">Durum</span>
                                <span class="kt-badge kt-badge-sm {{ !empty($u->is_active) ? 'kt-badge-success' : 'kt-badge-danger' }}">
                                    {{ !empty($u->is_active) ? 'Aktif' : 'Pasif' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-secondary-foreground">E-posta</span>
                                <span class="kt-badge kt-badge-sm {{ !empty($u->email_verified_at) ? 'kt-badge-success' : 'kt-badge-warning' }}">
                                    {{ !empty($u->email_verified_at) ? 'Doğrulandı' : 'Doğrulanmadı' }}
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-2">
                                @forelse($roles as $role)
                                    <span class="kt-badge kt-badge-light">{{ $role->name }}</span>
                                @empty
                                    <span class="text-sm text-muted-foreground">Rol atanmamış.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8">
                    <div class="kt-card">
                        <div class="kt-card-header py-5">
                            <div>
                                <h3 class="kt-card-title text-lg font-semibold">Profil Bilgileri</h3>
                                <div class="text-sm text-muted-foreground">Bu alanlar profil görüntüleme sayfasında gösterilir.</div>
                            </div>
                        </div>

                        <form method="POST"
                              action="{{ route('admin.profile.update') }}"
                              class="kt-card-content p-6 flex flex-col gap-6"
                              data-profile-form="true">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">İsim</label>
                                    <input class="kt-input @error('name') kt-input-invalid @enderror"
                                           name="name"
                                           value="{{ old('name', $u->name ?? '') }}"
                                           autocomplete="name">
                                    @error('name') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">E-posta</label>
                                    <input type="email"
                                           class="kt-input @error('email') kt-input-invalid @enderror"
                                           name="email"
                                           value="{{ old('email', $u->email ?? '') }}"
                                           autocomplete="email">
                                    @error('email') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">Unvan</label>
                                    <input class="kt-input @error('title') kt-input-invalid @enderror"
                                           name="title"
                                           value="{{ old('title', $u->title ?? '') }}"
                                           placeholder="Örn. Operasyon Yöneticisi">
                                    @error('title') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">Telefon</label>
                                    <input class="kt-input @error('phone') kt-input-invalid @enderror"
                                           name="phone"
                                           value="{{ old('phone', $u->phone ?? '') }}"
                                           autocomplete="tel">
                                    @error('phone') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">Şirket / Birim</label>
                                    <input class="kt-input @error('company') kt-input-invalid @enderror"
                                           name="company"
                                           value="{{ old('company', $u->company ?? '') }}">
                                    @error('company') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">Konum</label>
                                    <input class="kt-input @error('location') kt-input-invalid @enderror"
                                           name="location"
                                           value="{{ old('location', $u->location ?? '') }}"
                                           placeholder="Örn. İstanbul, Türkiye">
                                    @error('location') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">Web Sitesi</label>
                                    <input class="kt-input @error('website_url') kt-input-invalid @enderror"
                                           name="website_url"
                                           value="{{ old('website_url', $u->website_url ?? '') }}"
                                           placeholder="https://example.com">
                                    @error('website_url') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">LinkedIn</label>
                                    <input class="kt-input @error('linkedin_url') kt-input-invalid @enderror"
                                           name="linkedin_url"
                                           value="{{ old('linkedin_url', $u->linkedin_url ?? '') }}"
                                           placeholder="https://linkedin.com/in/kullanici">
                                    @error('linkedin_url') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">Yetenekler</label>
                                    <input class="kt-input @error('skills_text') kt-input-invalid @enderror"
                                           name="skills_text"
                                           value="{{ $skillsText }}"
                                           placeholder="Laravel, Yönetim, Operasyon, Müşteri Deneyimi">
                                    <div class="text-xs text-muted-foreground">Virgül, noktalı virgül veya satır sonu ile ayırabilirsin.</div>
                                    @error('skills_text') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="kt-form-label mb-2">Biyografi</label>
                                    <textarea class="kt-textarea min-h-[140px] @error('bio') kt-input-invalid @enderror"
                                              name="bio"
                                              placeholder="Kısa bir profesyonel tanıtım yazısı...">{{ old('bio', $u->bio ?? '') }}</textarea>
                                    @error('bio') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="rounded-xl border border-dashed border-border p-5">
                                <div class="flex flex-col gap-1 mb-4">
                                    <h4 class="text-sm font-semibold text-mono">Şifre Değiştir</h4>
                                    <p class="text-xs text-muted-foreground">Boş bırakırsan mevcut şifren korunur.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label class="kt-form-label mb-2">Yeni Şifre</label>
                                        <input type="password"
                                               class="kt-input @error('password') kt-input-invalid @enderror"
                                               name="password"
                                               data-password-input="true"
                                               autocomplete="new-password">
                                        @error('password') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label class="kt-form-label mb-2">Yeni Şifre Tekrar</label>
                                        <input type="password"
                                               class="kt-input @error('password_confirmation') kt-input-invalid @enderror"
                                               name="password_confirmation"
                                               data-password-confirmation-input="true"
                                               autocomplete="new-password">
                                        <div class="rounded-lg border border-warning/30 bg-warning/10 px-3 py-2 text-xs text-warning hidden" data-password-confirmation-message="true">
                                            Şifre tekrarı yeni şifre ile aynı olmalı.
                                        </div>
                                        @error('password_confirmation') <div class="text-xs text-danger">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.profile.index') }}" class="kt-btn kt-btn-light">İptal</a>
                                <button type="submit" class="kt-btn kt-btn-primary" data-profile-submit="true">Bilgileri Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
