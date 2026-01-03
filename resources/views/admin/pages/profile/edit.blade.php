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
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Avatar</h3>
                    </div>

                    <div class="kt-card-content flex flex-col gap-4">

                        {{-- Avatar FORM --}}
                        <form id="avatarForm" method="POST" action="{{ route('admin.profile.avatar') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                            @csrf

                            {{-- Media picker seçimi buraya yazılacak --}}
                            <input type="hidden" name="media_id" id="avatar_media_id" value="">

                            {{-- KT Image Input --}}
                            <div class="kt-image-input" data-kt-image-input="true">
                                <input type="file" accept=".png,.jpg,.jpeg,.webp" name="avatar" />

                                <button
                                    type="button"
                                    data-kt-tooltip="true"
                                    data-kt-tooltip-trigger="hover"
                                    data-kt-tooltip-placement="right"
                                    data-kt-image-input-remove="true"
                                    class="kt-image-input-remove"
                                >
                                    <i class="ki-filled ki-cross text-lg"></i>
                                    <span data-kt-tooltip-content="true" class="kt-tooltip">Kaldır / Geri al</span>
                                </button>

                                <div
                                    data-kt-image-input-placeholder="true"
                                    class="kt-image-input-placeholder"
                                    style="background-image:url('{{ asset('assets/media/blank.png') }}')"
                                >
                                    <div id="avatarPreview"
                                         data-kt-image-input-preview="true"
                                         class="kt-image-input-preview"
                                         style="background-image:url('{{ $avatarUrl }}')"></div>

                                    <div class="flex items-center justify-center cursor-pointer h-6 left-0 right-0 bottom-0 bg-black/25 absolute">
                                        <i class="ki-filled ki-camera text-white text-sm"></i>
                                    </div>
                                </div>
                            </div>

                            @error('avatar')
                            <div class="text-sm text-destructive">{{ $message }}</div>
                            @enderror

                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="kt-btn kt-btn-primary">
                                    <i class="ki-filled ki-check"></i>
                                    Avatarı Kaydet
                                </button>

                                <button type="button"
                                        class="kt-btn kt-btn-light"
                                        data-media-picker="true"
                                        data-media-picker-target="#avatar_media_id"
                                        data-media-picker-preview="#avatarPreviewImg"
                                        data-media-picker-mime="image/">
                                    <i class="ki-filled ki-some-files"></i>
                                    Medyadan Seç
                                </button>
                            </div>
                        </form>

                        {{-- Remove Avatar FORM --}}
                        <form method="POST" action="{{ route('admin.profile.avatar.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="kt-btn kt-btn-outline w-full">
                                <i class="ki-filled ki-trash"></i>
                                Avatarı Kaldır
                            </button>
                        </form>

                        <div class="text-xs text-muted-foreground leading-5">
                            Not: Avatar kaydı <b>avatar_media_id</b> üzerinden yapılır. Upload ettiğin görsel de otomatik media tablosuna kayıt açar.
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
