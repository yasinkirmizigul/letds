@extends('site.layouts.main.app')

@php
    $pageTitle = 'Hizmetlerimiz';
    $metaDescription = 'Araştırma tasarımı, veri analizi, akademik raporlama ve veri bilimi alanlarında uçtan uca istatistik danışmanlığı.';
    $consultationUrl = auth('member')->check()
        ? route('member.appointments.index', ['site_locale' => $siteCurrentLocale, 'open' => 1])
        : route('member.register', ['site_locale' => $siteCurrentLocale]);
@endphp

@section('content')
    <div class="site-services-page">
        <section class="site-services-hero">
            <span class="site-services-hero__grid" aria-hidden="true"></span>
            <div class="site-services-hero__inner">
                <div class="site-services-hero__copy" data-site-reveal>
                    <span class="site-services-kicker">PROBABLUE / Bilimsel Danışmanlık</span>
                    <h1>Araştırmanızın her aşamasında güvenilir istatistik desteği.</h1>
                    <p>Bilimsel yönteme dayalı, ihtiyaçlarınıza özel ve şeffaf danışmanlık çözümleri sunuyoruz.</p>
                    <div class="site-services-hero__actions">
                        <a href="{{ $consultationUrl }}" class="site-services-primary-cta" title="Ücretsiz ön görüşme planla">
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
                <section @if($loop->first) id="hizmetler" @endif class="site-services-section" style="--services-accent: {{ $section['accent_color'] }}">
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
                                @php($descriptionLines = collect(preg_split('/\r\n|\r|\n/', $item['description']))->map(fn ($line) => trim($line))->filter()->values())
                                @if($descriptionLines->count() > 1)
                                    <ul class="site-service-card__list">
                                        @foreach($descriptionLines as $line)
                                            <li>{{ $line }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p>{{ $item['description'] }}</p>
                                @endif
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
                <section @if($loop->first) id="nasil-ilerliyoruz" @endif class="site-services-process" style="--services-accent: {{ $section['accent_color'] }}">
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

                    <aside class="site-services-pricing" data-site-reveal>
                        <span class="site-services-kicker">Süre &amp; Fiyatlandırma</span>
                        <p>Çalışma süresi ve ücret; projenin kapsamı, uygulanacak testler, veri yapısı ve analiz yoğunluğuna göre belirlenir. Ön görüşmenin ardından tahmini teslim süresi ve fiyatlandırma şeffaf biçimde paylaşılır.</p>
                    </aside>
                </section>
            @endforeach

            <section id="birlikte-baslayalim" class="site-services-final-cta" data-site-reveal>
                <div>
                    <span class="site-services-kicker">İlk adım</span>
                    <h2>Projenizi birlikte değerlendirelim.</h2>
                    <p>Önce hesabınızı oluşturun; ardından size uygun uzmanı ve görüşme saatini seçin.</p>
                </div>
                <a href="{{ $consultationUrl }}" class="site-services-primary-cta" title="Ön görüşme randevusu oluştur">
                    Randevu Oluştur
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </section>
        </div>
    </div>
@endsection
