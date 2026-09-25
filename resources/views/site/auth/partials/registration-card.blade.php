@php
    $registrationTitleId = $registrationTitleId ?? 'registration-title';
    $registrationSource = $registrationSource ?? 'register';
@endphp

<section class="site-auth-form-card site-registration-card" aria-labelledby="{{ $registrationTitleId }}">
    <div class="site-auth-form-card__heading site-registration-card__heading">
        <div class="site-registration-card__kicker">Kayıt Ol</div>
        <h2 id="{{ $registrationTitleId }}">Ön görüşme planlama sürecine başlayın.</h2>
        <p>Bilgilerinizi oluşturun; kayıt tamamlanınca randevu adımı otomatik açılır.</p>
    </div>

    <nav class="site-onboarding-tabs" aria-label="Başlangıç süreci">
        <span class="site-onboarding-tab is-active" aria-current="step">
            <i class="fa-regular fa-user" aria-hidden="true"></i>
            <span>Kayıt Ol</span>
        </span>
        <span class="site-onboarding-tab is-locked" aria-disabled="true">
            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
            <span>Randevu Oluştur</span>
            <i class="fa-solid fa-lock site-onboarding-tab__lock" aria-hidden="true"></i>
        </span>
    </nav>

    <form id="member-registration-form" method="POST" action="{{ route('member.register.post') }}" class="site-registration-form">
        @csrf
        <input type="hidden" name="_registration_source" value="{{ $registrationSource }}">

        <div class="site-registration-fields">
            <div class="grid gap-2 site-registration-field">
                <label for="member_register_name">Ad</label>
                <div class="kt-input site-registration-input @error('name') kt-input-invalid @enderror">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    <input id="member_register_name" type="text" name="name" value="{{ old('name') }}" placeholder="Adınız" autocomplete="given-name" required>
                </div>
                @error('name')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="grid gap-2 site-registration-field">
                <label for="member_register_surname">Soyad</label>
                <div class="kt-input site-registration-input @error('surname') kt-input-invalid @enderror">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    <input id="member_register_surname" type="text" name="surname" value="{{ old('surname') }}" placeholder="Soyadınız" autocomplete="family-name" required>
                </div>
                @error('surname')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="grid gap-2 site-registration-field">
                <label for="member_register_email">E-posta</label>
                <div class="kt-input site-registration-input @error('email') kt-input-invalid @enderror">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                    <input id="member_register_email" type="email" name="email" value="{{ old('email') }}" placeholder="ornek@mail.com" autocomplete="email" required>
                </div>
                @error('email')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="grid gap-2 site-registration-field">
                <label for="member_register_institution">Kurum / Üniversite</label>
                <div class="kt-input site-registration-input @error('institution') kt-input-invalid @enderror">
                    <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                    <input id="member_register_institution" type="text" name="institution" value="{{ old('institution') }}" placeholder="Kurum veya üniversite adınız" autocomplete="organization">
                </div>
                @error('institution')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="grid gap-2 site-registration-field site-registration-field--wide">
                <label for="member_register_phone">Telefon</label>
                <div class="kt-input site-registration-input @error('phone') kt-input-invalid @enderror">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <input id="member_register_phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="5XX XXX XX XX" autocomplete="tel">
                </div>
                @error('phone')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="grid gap-2 site-registration-field">
                <label for="member_register_password">Şifre</label>
                <div class="kt-input site-registration-input @error('password') kt-input-invalid @enderror" data-kt-toggle-password="true">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input id="member_register_password" type="password" name="password" placeholder="En az 8 karakter" autocomplete="new-password" required>
                    <button type="button" class="site-registration-password-toggle" data-kt-toggle-password-trigger="true" aria-label="Şifre görünürlüğünü değiştir">
                        <span class="kt-toggle-password-active:hidden"><i class="fa-regular fa-eye" aria-hidden="true"></i></span>
                        <span class="hidden kt-toggle-password-active:block"><i class="fa-regular fa-eye-slash" aria-hidden="true"></i></span>
                    </button>
                </div>
                @error('password')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="grid gap-2 site-registration-field">
                <label for="member_register_password_confirmation">Şifre Tekrar</label>
                <div class="kt-input site-registration-input" data-kt-toggle-password="true">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input id="member_register_password_confirmation" type="password" name="password_confirmation" placeholder="Şifrenizi tekrar girin" autocomplete="new-password" required>
                    <button type="button" class="site-registration-password-toggle" data-kt-toggle-password-trigger="true" aria-label="Şifre tekrarının görünürlüğünü değiştir">
                        <span class="kt-toggle-password-active:hidden"><i class="fa-regular fa-eye" aria-hidden="true"></i></span>
                        <span class="hidden kt-toggle-password-active:block"><i class="fa-regular fa-eye-slash" aria-hidden="true"></i></span>
                    </button>
                </div>
            </div>
        </div>

        <input type="hidden" name="membership_terms_read" value="{{ $hasReadTerms ? 1 : 0 }}" data-membership-terms-read-input>

        <div class="site-registration-terms">
            <div>
                <strong>{{ $membershipTermsTitle }}</strong>
                <p>{{ $membershipTermsSummary }}</p>
            </div>
            <button type="button" class="kt-btn kt-btn-light" data-membership-terms-open>Metni Oku</button>
            <label>
                <input type="checkbox" name="membership_terms_accepted" value="1" class="kt-checkbox" data-membership-terms-checkbox @checked(old('membership_terms_accepted')) @disabled(!$hasReadTerms)>
                <span>Üyelik bilgilendirme metnini okudum ve kabul ediyorum.</span>
            </label>
            <small data-membership-terms-status>Metni sonuna kadar okuduğunuzda onay kutusu aktif olur.</small>
            @error('membership_terms_read')<div class="text-xs text-danger">{{ $message }}</div>@enderror
            @error('membership_terms_accepted')<div class="text-xs text-danger">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="site-registration-submit">
            Devam Et <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>
        <p class="site-registration-submit-note"><i class="fa-solid fa-lock" aria-hidden="true"></i> Kayıt sonrası randevu seçiminize geçeceksiniz.</p>
    </form>

    <div class="site-registration-next">
        <div>
            <strong>Sonraki Adım: Ön Görüşme Randevusu</strong>
            <span>Kayıt işleminiz tamamlandığında randevunuzu üç kısa seçimle oluşturabilirsiniz.</span>
        </div>
        <ul>
            <li><i class="fa-regular fa-user" aria-hidden="true"></i><span><strong>Uzman Seçimi</strong><small>Size uygun uzman</small></span></li>
            <li><i class="fa-regular fa-calendar" aria-hidden="true"></i><span><strong>Tarih &amp; Saat</strong><small>Uygun görüşme zamanı</small></span></li>
            <li><i class="fa-solid fa-list" aria-hidden="true"></i><span><strong>Destek Konusu</strong><small>Görüşme ihtiyacınız</small></span></li>
        </ul>
    </div>

    <div class="site-registration-login">
        Zaten hesabınız var mı?
        <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}">Giriş Yap</a>
    </div>
</section>
