@extends('admin.layouts.main.app')

@section('content')
    @php($u = auth()->user())

    <div class="kt-container-fixed">
        @includeIf('admin.partials._flash')

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold">Profil</h1>
                <div class="text-sm text-muted-foreground">Hesap bilgilerini düzenle</div>
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
                        @php
                            // DB'de avatar alanı yoksa statik gösteriyoruz.
                            // Eğer ileride avatarı gerçek yapacaksan:
                            // - users.avatar kolonu ekle, ya da
                            // - media tablosu ile ilişki kur.
                            $avatarUrl = asset('assets/media/avatars/300-1.png');
                            $blankUrl  = asset('assets/media/avatars/blank.png');
                            $avatarRouteExists = \Illuminate\Support\Facades\Route::has('admin.profile.avatar');
                        @endphp

                        <div class="text-sm text-muted-foreground leading-5">
                            Bu panelde avatar alanı veritabanında tanımlı değilse sadece görsel (UI) olarak kalır.
                            Gerçek kaydetme için backend tarafını bağlaman gerekir.
                        </div>

                        {{-- KT Image Input --}}
                        <div class="kt-image-input" data-kt-image-input="true">
                            <input type="file" accept=".png, .jpg, .jpeg" name="avatar" {{ $avatarRouteExists ? '' : 'disabled' }} />
                            <input type="hidden" name="avatar_remove" />

                            <button
                                type="button"
                                data-kt-tooltip="true"
                                data-kt-tooltip-trigger="hover"
                                data-kt-tooltip-placement="right"
                                data-kt-image-input-remove="true"
                                class="kt-image-input-remove"
                                {{ $avatarRouteExists ? '' : 'disabled' }}
                            >
                                <i class="ki-filled ki-cross text-lg"></i>
                                <span data-kt-tooltip-content="true" class="kt-tooltip">Kaldır / Geri al</span>
                            </button>

                            <div
                                data-kt-image-input-placeholder="true"
                                class="kt-image-input-placeholder"
                                style="background-image: url('{{ $blankUrl }}')"
                            >
                                <div
                                    data-kt-image-input-preview="true"
                                    class="kt-image-input-preview"
                                    style="background-image: url('{{ $avatarUrl }}')"
                                ></div>

                                <div class="flex items-center justify-center cursor-pointer h-6 left-0 right-0 bottom-0 bg-black/25 absolute">
                                    <i class="ki-filled ki-camera text-white text-sm"></i>
                                </div>
                            </div>
                        </div>

                        @if(!$avatarRouteExists)
                            <div class="kt-alert kt-alert-light border border-border">
                                <div class="kt-alert-title">Avatar kaydı kapalı</div>
                                <div class="kt-alert-text">
                                    <code>admin.profile.avatar</code> route’u yok. UI görünüyor ama kaydetmez.
                                </div>
                            </div>
                        @else
                            {{-- Eğer route varsa: ayrı avatar formu --}}
                            <form method="POST" action="{{ route('admin.profile.avatar') }}" enctype="multipart/form-data" class="flex gap-2">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-primary">
                                    <i class="ki-filled ki-check"></i>
                                    Avatarı Kaydet
                                </button>
                                <button type="button" class="kt-btn kt-btn-light" data-kt-modal-toggle="#media_picker_modal">
                                    <i class="ki-filled ki-some-files"></i>
                                    Medyadan Seç
                                </button>
                            </form>
                        @endif
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
                                <span class="kt-badge kt-badge-primary kt-badge-outline">
                                Email doğrulandı
                            </span>
                            @else
                                <span class="kt-badge kt-badge-outline">
                                Email doğrulanmadı
                            </span>
                            @endif

                            @if($u->is_active)
                                <span class="kt-badge kt-badge-success kt-badge-outline">
                                Aktif
                            </span>
                            @else
                                <span class="kt-badge kt-badge-danger kt-badge-outline">
                                Pasif
                            </span>
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

                {{-- Media Picker modal (mevcut yapın) --}}
                @include('admin.partials.media._picker-modal')
            </div>
        </div>
    </div>
@endsection
