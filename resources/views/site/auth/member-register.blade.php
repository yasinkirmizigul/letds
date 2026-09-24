@extends('site.layouts.main.app')

@php
    $membershipTermsTitle = $siteSettings->localized('member_terms_title') ?: config('membership_terms.title');
    $membershipTermsSummary = $siteSettings->localized('member_terms_summary') ?: config('membership_terms.summary');
    $membershipTermsContent = $siteSettings->localized('member_terms_content') ?: config('membership_terms.content');
    $hasReadTerms = old('membership_terms_read') === '1' || old('membership_terms_read') === 1;
@endphp

@section('content')
    <div class="site-auth-page site-registration-page mx-auto max-w-[96rem] px-3 py-10 sm:px-4 lg:px-6">
        <div class="site-auth-split site-registration-layout grid">
            <section class="site-auth-story site-registration-story">
                <div>
                    <div class="site-registration-story__eyebrow">
                        <span>03</span>
                        <strong>Birlikte Başlayalım</strong>
                    </div>
                    <h1>Ön görüşme sürecini kayıt oluşturarak başlatın.</h1>
                    <p>Hesabınızı oluşturun, ardından size uygun uzmanı ve görüşme saatini seçin.</p>
                </div>

                <ol class="site-registration-story__steps" aria-label="Başlangıç adımları">
                    <li>
                        <span><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                        <div><strong>Kayıt Olun</strong><small>İletişim ve kurum bilgilerinizle güvenli hesabınızı oluşturun.</small></div>
                    </li>
                    <li>
                        <span><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
                        <div><strong>Randevunuzu Oluşturun</strong><small>Uzman, destek konusu, tarih ve saat seçiminizi tamamlayın.</small></div>
                    </li>
                    <li>
                        <span><i class="fa-regular fa-bell" aria-hidden="true"></i></span>
                        <div><strong>Hatırlatma Alın</strong><small>Randevu onayı ve süreç bilgileri hesabınıza iletilsin.</small></div>
                    </li>
                </ol>

                <a href="#member-registration-form" class="site-registration-story__cta">
                    Kayıt Ol <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>

                <p class="site-registration-story__note">
                    Kayıt sırasında belge istemiyoruz. Dosyalarınızı ön görüşme sonrasında çalışma alanınızdan paylaşabilirsiniz.
                </p>
            </section>

            <section class="site-auth-form-card site-registration-card" aria-labelledby="registration-title">
                <div class="site-auth-form-card__heading site-registration-card__heading">
                    <div class="site-registration-card__kicker">Kayıt Ol</div>
                    <h2 id="registration-title">Ön görüşme planlama sürecine başlayın.</h2>
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
        </div>
    </div>

    <div class="fixed inset-0 z-[120] hidden bg-foreground/60 p-2 sm:px-4 sm:py-6" data-membership-terms-modal>
        <div class="mx-auto flex h-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-border bg-background shadow-2xl sm:rounded-3xl">
            <div class="flex items-start justify-between gap-3 border-b border-border px-4 py-4 sm:gap-4 sm:px-6 sm:py-5">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Üyelik Bilgilendirmesi</div>
                    <h3 class="mt-2 font-display text-xl font-semibold text-foreground sm:text-2xl">{{ $membershipTermsTitle }}</h3>
                    <div class="mt-2 text-sm text-muted-foreground">{{ $membershipTermsSummary }}</div>
                </div>
                <button type="button" class="kt-btn kt-btn-light" data-membership-terms-close>Kapat</button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5 text-sm leading-7 text-muted-foreground sm:px-6 sm:py-6 sm:leading-8" data-membership-terms-scrollable>
                {!! nl2br(e($membershipTermsContent)) !!}
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-4 sm:px-6 sm:py-5">
                <div class="text-sm text-muted-foreground" data-membership-terms-modal-status>Metnin sonuna ulaştığınızda kabul kutusu aktifleşir.</div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('member.terms.show', ['site_locale' => $siteCurrentLocale]) }}" target="_blank" class="kt-btn kt-btn-light">Tam Sayfada Aç</a>
                    <button type="button" class="kt-btn kt-btn-primary" data-membership-terms-close>Okudum, Forma Dön</button>
                </div>
            </div>
        </div>
    </div>
@endsection
