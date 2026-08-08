@extends('site.layouts.main.app')

@section('content')
    <div class="site-page site-page-narrow">
        @include('site.partials.member-nav')

        <section class="mt-7 site-page-hero" data-reveal>
            <span class="site-eyebrow">Hesap ayarları</span>
            <h1 class="site-title">Bilgileriniz, kontrolünüzde.</h1>
            <p class="site-lead">İletişim bilgilerinizi güncelleyin veya hesabınız için yeni bir parola belirleyin.</p>
        </section>

        <form method="POST" action="{{ route('member.account.update', ['site_locale' => $siteCurrentLocale]) }}" class="mt-8 grid gap-7 rounded-3xl border border-border bg-background p-6 shadow-sm md:p-9">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="rounded-2xl border border-danger/25 bg-danger/10 px-4 py-4 text-sm text-danger">Lütfen işaretlenen alanları kontrol edin.</div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="member_name" class="kt-form-label">Ad</label>
                    <input id="member_name" name="name" value="{{ old('name', $member->name) }}" class="kt-input @error('name') kt-input-invalid @enderror" autocomplete="given-name" required>
                    @error('name')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="grid gap-2">
                    <label for="member_surname" class="kt-form-label">Soyad</label>
                    <input id="member_surname" name="surname" value="{{ old('surname', $member->surname) }}" class="kt-input @error('surname') kt-input-invalid @enderror" autocomplete="family-name" required>
                    @error('surname')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="grid gap-2">
                    <label for="member_email" class="kt-form-label">E-posta</label>
                    <input id="member_email" type="email" name="email" value="{{ old('email', $member->email) }}" class="kt-input @error('email') kt-input-invalid @enderror" autocomplete="email" required>
                    @error('email')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="grid gap-2">
                    <label for="member_phone" class="kt-form-label">Telefon</label>
                    <input id="member_phone" name="phone" value="{{ old('phone', $member->phone) }}" class="kt-input @error('phone') kt-input-invalid @enderror" autocomplete="tel" placeholder="05xx xxx xx xx">
                    @error('phone')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="border-t border-border pt-7">
                <div class="mb-5">
                    <h2 class="site-section-title !text-2xl">Güvenlik</h2>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">E-posta veya parola değişikliğinde mevcut parolanızla doğrulama yapmanız gerekir.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2">
                        <label for="member_current_password" class="kt-form-label">Mevcut parola</label>
                        <input id="member_current_password" type="password" name="current_password" class="kt-input @error('current_password') kt-input-invalid @enderror" autocomplete="current-password">
                        @error('current_password')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label for="member_password" class="kt-form-label">Yeni parola</label>
                        <input id="member_password" type="password" name="password" class="kt-input @error('password') kt-input-invalid @enderror" autocomplete="new-password">
                        @error('password')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="grid gap-2">
                        <label for="member_password_confirmation" class="kt-form-label">Yeni parola tekrarı</label>
                        <input id="member_password_confirmation" type="password" name="password_confirmation" class="kt-input" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-border pt-6">
                <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">Vazgeç</a>
                <button type="submit" class="kt-btn kt-btn-primary">Bilgilerimi güncelle</button>
            </div>
        </form>
    </div>
@endsection
