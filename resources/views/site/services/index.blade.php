@extends('site.layouts.main.app')

@php
    $pageTitle = 'Hizmetlerimiz';
    $metaDescription = 'Araştırma tasarımı, veri analizi, akademik raporlama ve veri bilimi alanlarında uçtan uca istatistik danışmanlığı.';
@endphp

@section('content')
    <div class="site-services-page">
        <section class="site-services-hero">
            <span class="site-services-hero__grid" aria-hidden="true"></span>
            <div class="site-services-hero__inner">
                <div class="site-services-hero__copy" data-site-reveal>
                    <span class="site-services-kicker">PROBABLUE / Bilimsel Danışmanlık</span>
                    <h1>Araştırmanızın her aşamasında güvenilir istatistik desteği.</h1>
                    <p>
                        Doğru yöntem, şeffaf süreç ve anlaşılır çıktılarla araştırma fikrinizi güçlü bir sonuca dönüştürüyoruz.
                    </p>
                    <div class="site-services-hero__actions">
                        <a href="{{ route('member.appointments.index') }}" class="site-services-primary-cta" title="Ücretsiz ön görüşme planla">
                            <span>Ücretsiz Ön Görüşme Planla</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <a href="#hizmetler" class="site-services-secondary-cta" title="Hizmetleri keşfet">
                            Hizmetleri Keşfet
                        </a>
                    </div>
                    <div class="site-services-hero__trust">
                        <span><i class="fa-solid fa-circle-check"></i> Bilimsel yöntem</span>
                        <span><i class="fa-solid fa-circle-check"></i> Gizlilik odaklı</span>
                        <span><i class="fa-solid fa-circle-check"></i> Açık iletişim</span>
                    </div>
                </div>

                <div class="site-services-hero__visual" aria-hidden="true" data-site-reveal>
                    <div class="site-services-mark">
                        <span class="site-services-mark__p">P</span>
                        <span class="site-services-mark__axis site-services-mark__axis--x"></span>
                        <span class="site-services-mark__axis site-services-mark__axis--y"></span>
                        <span class="site-services-mark__bar site-services-mark__bar--one"></span>
                        <span class="site-services-mark__bar site-services-mark__bar--two"></span>
                        <span class="site-services-mark__bar site-services-mark__bar--three"></span>
                        <span class="site-services-mark__trend"></span>
                    </div>
                    <span class="site-services-hero__orbit site-services-hero__orbit--one"></span>
                    <span class="site-services-hero__orbit site-services-hero__orbit--two"></span>
                </div>
            </div>
        </section>

        <div class="site-services-content">
            @forelse($serviceSections as $section)
                <section id="hizmetler" class="site-services-section" style="--services-accent: {{ $section['accent_color'] }}">
                    <header class="site-services-section__header" data-site-reveal>
                        @if($section['eyebrow'])
                            <span class="site-services-kicker">{{ $section['eyebrow'] }}</span>
                        @endif
                        <h2>{{ $section['title'] }}</h2>
                        @if($section['description'])
                            <p>{{ $section['description'] }}</p>
                        @endif
                    </header>

                    <div class="site-service-grid" style="--service-columns: {{ min(3, $section['columns']) }}">
                        @foreach($section['items'] as $index => $item)
                            <article class="site-service-card" data-site-reveal>
                                <div class="site-service-card__topline">
                                    <span class="site-service-card__number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="site-service-card__icon">
                                        @include('site.services.partials.icon', ['icon' => $item['icon']])
                                    </span>
                                </div>
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <section id="hizmetler" class="site-services-empty">
                    Hizmet içerikleri hazırlanıyor. Ön görüşme için randevu oluşturabilirsiniz.
                </section>
            @endforelse

            @foreach($processSections as $section)
                <section class="site-services-process" style="--services-accent: {{ $section['accent_color'] }}">
                    <header class="site-services-section__header site-services-section__header--center" data-site-reveal>
                        @if($section['eyebrow'])
                            <span class="site-services-kicker">{{ $section['eyebrow'] }}</span>
                        @endif
                        <h2>{{ $section['title'] }}</h2>
                        @if($section['description'])
                            <p>{{ $section['description'] }}</p>
                        @endif
                    </header>

                    <div class="site-process-grid">
                        @foreach($section['items'] as $index => $item)
                            <article class="site-process-step" data-site-reveal>
                                <span class="site-process-step__index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="site-process-step__icon">
                                    @include('site.services.partials.icon', ['icon' => $item['icon']])
                                </span>
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <section class="site-services-final-cta" data-site-reveal>
                <div>
                    <span class="site-services-kicker">İlk adım</span>
                    <h2>Projenizi birlikte değerlendirelim.</h2>
                    <p>Üye hesabınızla giriş yapın, size uygun uzmanı ve görüşme saatini seçin.</p>
                </div>
                <a href="{{ route('member.appointments.index') }}" class="site-services-primary-cta" title="Ön görüşme randevusu oluştur">
                    Randevu Oluştur
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </section>
        </div>
    </div>
@endsection
