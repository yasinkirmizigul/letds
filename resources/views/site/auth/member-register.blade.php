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

            @include('site.auth.partials.registration-card')
        </div>
    </div>

    @include('site.auth.partials.membership-terms-modal')
@endsection
