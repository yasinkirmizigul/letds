@extends('admin.layouts.main.app')

@section('content')
    @php($u = $user ?? auth()->user())
    @php($avatarUrl = method_exists($u, 'avatarUrl') ? $u->avatarUrl() : asset('assets/media/blank.png'))

    <div class="kt-container-fixed max-w-[90%]" data-page="profile.edit">
        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Profil</h1>
                <div class="text-sm text-muted-foreground">Profil bilgilerini ve avatarını düzenle</div>
            </div>

            <a href="{{ route('admin.profile.index') }}" class="kt-btn kt-btn-light">
                Geri
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            {{-- LEFT: Avatar --}}
            <div class="lg:col-span-1">
                {{-- LEFT: Avatar --}}
                <div class="kt-card">
                    <div class="kt-card-header py-5">
                        <h3 class="kt-card-title text-lg font-semibold">Avatar</h3>
                    </div>

                    <div class="kt-card-content p-6 flex flex-col gap-4">
                        {{-- Avatar Update --}}
                        {{-- Avatar --}}
                        <form method="POST" action="{{ route('admin.profile.update') }}" class="flex flex-col gap-4">
                            @csrf
                            @method('PUT')

                            {{-- picker buraya id yazacak --}}
                            <input type="hidden"
                                   name="avatar_media_id"
                                   id="avatarMediaId"
                                   value="{{ old('avatar_media_id', $u->avatar_media_id) }}">

                            <div class="flex items-center gap-4">

                                <div class="kt-image-input"
                                     data-kt-image-input="true">

                                    {{-- PREVIEW (KTUI bunu şart koşuyor) --}}
                                    <div id="avatarPreview"
                                         data-kt-image-input-preview="true"
                                         class="kt-image-input-preview w-20 h-20 rounded-full border border-border bg-cover bg-center"
                                         style="background-image:url('{{ $avatarUrl }}')">
                                    </div>

                                    {{-- CHANGE (dosyadan seçmek istersen) --}}
                                    <label class="kt-btn kt-btn-icon kt-btn-circle kt-btn-xs kt-btn-primary"
                                           data-kt-image-input-action="change"
                                           title="Dosyadan seç">
                                        <i class="ki-filled ki-pencil"></i>
                                        <input type="file" name="avatar_file" accept=".png,.jpg,.jpeg,.webp">
                                        <input type="hidden" name="avatar_remove" value="0">
                                    </label>

                                    {{-- CANCEL --}}
                                    <span class="kt-btn kt-btn-icon kt-btn-circle kt-btn-xs kt-btn-light"
                                          data-kt-image-input-action="cancel"
                                          title="İptal">
                <i class="ki-filled ki-cross"></i>
            </span>

                                    {{-- REMOVE (KT UI remove) --}}
                                    <span class="kt-btn kt-btn-icon kt-btn-circle kt-btn-xs kt-btn-danger"
                                          data-kt-image-input-action="remove"
                                          title="Kaldır">
                <i class="ki-filled ki-trash"></i>
            </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Media picker ile seç --}}
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

                        {{-- Tam kaldırma (DB’yi null’la) --}}
                        <form method="POST" action="{{ route('admin.profile.avatar.remove') }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="kt-btn kt-btn-danger kt-btn-sm">Avatarı Kaldır</button>
                        </form>

                        <div class="text-xs text-muted-foreground">
                            Not: Avatar kaydı avatar_media_id üzerinden yapılır.
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Profile form --}}
            <div class="lg:col-span-2">
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Bilgiler</h3>
                    </div>

                    <form method="POST" action="{{ route('admin.profile.update') }}" class="kt-card-content p-8 flex flex-col gap-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="flex flex-col gap-1.5">
                                <label class="kt-label">İsim</label>
                                <input
                                    class="kt-input"
                                    name="name"
                                    value="{{ old('name', $u->name) }}"
                                    required
                                />
                                @error('name')
                                <div class="text-sm text-destructive">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="kt-label">Email</label>
                                <input
                                    class="kt-input"
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $u->email) }}"
                                    required
                                />
                                @error('email')
                                <div class="text-sm text-destructive">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if($u->email_verified_at)
                                <span class="kt-badge kt-badge-primary kt-badge-outline">Email doğrulandı</span>
                            @else
                                <span class="kt-badge kt-badge-outline">Email doğrulanmadı</span>
                            @endif

                            @if($u->is_active)
                                <span class="kt-badge kt-badge-success kt-badge-outline">Aktif</span>
                            @else
                                <span class="kt-badge kt-badge-danger kt-badge-outline">Pasif</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.profile.index') }}" class="kt-btn kt-btn-light">İptal</a>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
