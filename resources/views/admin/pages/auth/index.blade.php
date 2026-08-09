@extends('admin.layouts.auth.base')

@section('content')
    @php
        $brandName = trim((string) ($siteSettings?->site_name ?: config('app.name')));
        $brandTagline = trim((string) ($siteSettings?->site_tagline ?: 'İçerik ve operasyon yönetim merkezi'));
        $brandInitial = mb_strtoupper(mb_substr($brandName, 0, 1));
    @endphp

    <main class="admin-auth-page">
        <button
            type="button"
            class="admin-auth-theme-toggle"
            data-kt-theme-switch-toggle="true"
            aria-label="Açık ve koyu tema arasında geçiş yap"
        >
            <span class="admin-auth-theme-toggle__light">
                <i class="ki-filled ki-moon"></i>
                Koyu Mod
            </span>
            <span class="admin-auth-theme-toggle__dark">
                <i class="ki-filled ki-sun"></i>
                Açık Mod
            </span>
        </button>

        <div class="admin-auth-orbit admin-auth-orbit--one" aria-hidden="true"></div>
        <div class="admin-auth-orbit admin-auth-orbit--two" aria-hidden="true"></div>

        <div class="admin-auth-shell">
            <aside class="admin-auth-story">
                <div class="admin-auth-story__top">
                    <div class="admin-auth-brand">
                        @if($adminLoginLogo)
                            <img src="{{ $adminLoginLogo->url() }}" alt="{{ $brandName }}" class="admin-auth-brand__logo">
                        @else
                            <span class="admin-auth-brand__fallback" aria-hidden="true">{{ $brandInitial }}</span>
                            <span class="admin-auth-brand__name">{{ $brandName }}</span>
                        @endif
                    </div>

                    <span class="admin-auth-story__badge">
                        <span></span>
                        Güvenli yönetim alanı
                    </span>
                </div>

                <div class="admin-auth-story__content">
                    <span class="admin-auth-kicker">Yönetim çalışma alanı</span>
                    <h1>İçeriği, operasyonu ve büyümeyi tek merkezden yönetin.</h1>
                    <p>{{ $brandTagline }}</p>
                </div>

                <div class="admin-auth-story__footer">
                    <div>
                        <strong>Tek panel</strong>
                        <span>İçerik, müşteri ve operasyon akışları</span>
                    </div>
                    <div>
                        <strong>Güvenli erişim</strong>
                        <span>Rol ve yetki tabanlı çalışma düzeni</span>
                    </div>
                </div>
            </aside>

            <section class="admin-auth-form-panel">
                <div class="admin-auth-mobile-brand">
                    @if($adminLoginLogo)
                        <img src="{{ $adminLoginLogo->url() }}" alt="{{ $brandName }}">
                    @else
                        <span>{{ $brandInitial }}</span>
                        <strong>{{ $brandName }}</strong>
                    @endif
                </div>

                <div class="admin-auth-form-heading">
                    <span class="admin-auth-kicker">Dashboard girişi</span>
                    <h2>Tekrar hoş geldiniz</h2>
                    <p>Devam etmek için yönetim hesabınızla güvenli giriş yapın.</p>
                </div>

                <form action="{{ route('login.post') }}" class="admin-auth-form" id="sign_in_form" method="POST">
                    @csrf

                    @if(session('success'))
                        <div class="kt-alert kt-alert-success">
                            <div class="kt-alert-text">{{ session('success') }}</div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="kt-alert kt-alert-danger">
                            <div class="kt-alert-title">Giriş yapılamadı</div>
                            <div class="kt-alert-text">
                                <ul class="list-disc space-y-1 ps-5">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="admin-auth-field">
                        <label for="admin_login_email">E-posta</label>
                        <div class="admin-auth-input @error('email') is-invalid @enderror">
                            <i class="ki-filled ki-sms"></i>
                            <input
                                id="admin_login_email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="ornek@firma.com"
                                autocomplete="email"
                                required
                                autofocus
                            >
                        </div>
                        @error('email')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="admin-auth-field">
                        <div class="admin-auth-field__label-row">
                            <label for="admin_login_password">Şifre</label>
                            <a href="{{ route('password.request') }}">Şifremi unuttum</a>
                        </div>

                        <div class="admin-auth-input @error('password') is-invalid @enderror" data-kt-toggle-password="true">
                            <i class="ki-filled ki-lock-2"></i>
                            <input
                                id="admin_login_password"
                                name="password"
                                placeholder="Şifrenizi girin"
                                type="password"
                                autocomplete="current-password"
                                required
                            >
                            <button class="admin-auth-password-toggle" data-kt-toggle-password-trigger="true" type="button" aria-label="Şifre görünürlüğünü değiştir">
                                <span class="kt-toggle-password-active:hidden"><i class="ki-filled ki-eye"></i></span>
                                <span class="hidden kt-toggle-password-active:block"><i class="ki-filled ki-eye-slash"></i></span>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-xs text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="admin-auth-form__options">
                        <label class="admin-auth-remember" for="admin_login_remember">
                            <input
                                id="admin_login_remember"
                                class="kt-checkbox"
                                name="remember"
                                type="checkbox"
                                value="1"
                                @checked(old('remember'))
                            >
                            <span>Beni Hatırla</span>
                        </label>
                        <span class="admin-auth-security-note"><i class="ki-filled ki-shield-tick"></i> Korumalı oturum</span>
                    </div>

                    <button class="admin-auth-submit" type="submit">
                        <span>Dashboard’a Giriş Yap</span>
                        <i class="ki-filled ki-arrow-right"></i>
                    </button>
                </form>

                <div class="admin-auth-form-footer">
                    <i class="ki-filled ki-information-2"></i>
                    Yetkisiz erişim denemeleri güvenlik amacıyla kayıt altına alınır.
                </div>
            </section>
        </div>
    </main>
@endsection
