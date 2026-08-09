@if($aboutPage || $homepageFaqs->isNotEmpty())
    <section class="home-discovery-section" aria-labelledby="home-discovery-title">
        <div class="home-discovery-glow" aria-hidden="true"></div>
        <div class="home-feature-container home-discovery-layout">
            @if($aboutPage)
                <article class="home-about-panel et-in-viewport-check" et-anim="feature-rise" et-anim-duration="620" et-anim-delay="0" et-anim-easing="cubic-bezier(.2,.8,.2,1)">
                    <span class="home-discovery-eyebrow">{{ $aboutPage->localized('hero_kicker') ?: 'Hakkımızda' }}</span>
                    <h2 id="home-discovery-title">{{ $aboutPage->localized('title') }}</h2>
                    <p>{{ $aboutPage->localized('excerpt') }}</p>

                    <div class="home-about-panel__meta">
                        <span>
                            <strong>Şeffaf</strong>
                            <small>İletişim</small>
                        </span>
                        <span>
                            <strong>Ölçülebilir</strong>
                            <small>İlerleme</small>
                        </span>
                        <span>
                            <strong>Sürdürülebilir</strong>
                            <small>Sonuç</small>
                        </span>
                    </div>

                    <a href="{{ $aboutPage->publicUrl($locale) }}" class="home-discovery-link">
                        <span>Hikayemizi Keşfedin</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </article>
            @endif

            @if($homepageFaqs->isNotEmpty())
                <div class="home-faq-panel et-in-viewport-check" et-anim="feature-rise" et-anim-duration="620" et-anim-delay="100" et-anim-easing="cubic-bezier(.2,.8,.2,1)">
                    <header class="home-faq-panel__header">
                        <div>
                            <span class="home-discovery-eyebrow">{{ $siteSettings->uiLine('home_faq_kicker') }}</span>
                            <h2>{{ $siteSettings->uiLine('home_faq_heading') }}</h2>
                        </div>
                        <span class="home-faq-panel__count">{{ str_pad((string) $homepageFaqs->count(), 2, '0', STR_PAD_LEFT) }}</span>
                    </header>

                    <p class="home-faq-panel__intro">{{ $siteSettings->uiLine('home_faq_description') }}</p>

                    <div class="home-faq-list">
                        @foreach($homepageFaqs as $faq)
                            <details @if($loop->first) open @endif>
                                <summary>
                                    <span>{{ $faq->localized('question') }}</span>
                                    <span class="home-faq-list__toggle" aria-hidden="true">+</span>
                                </summary>
                                <div>{!! nl2br(e($faq->localized('answer'))) !!}</div>
                            </details>
                        @endforeach
                    </div>

                    <a href="{{ $faqUrl }}" class="home-discovery-link home-discovery-link--light">
                        <span>{{ $siteSettings->uiLine('home_faq_cta_label') }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </div>
    </section>
@endif

<footer class="home-site-footer">
    <div class="home-feature-container home-site-footer__inner">
        <div>
            <strong>{{ $siteName }}</strong>
            <span>{{ $siteSettings->localized('site_tagline') ?: 'Dijital vitrin ve içerik yönetimi' }}</span>
        </div>

        @if($sitePrimaryNavigation->isNotEmpty())
            <nav aria-label="Site bağlantıları">
                @foreach($sitePrimaryNavigation as $navItem)
                    <a href="{{ $navItem->resolvedUrl($locale) }}" @if($navItem->target === '_blank') target="_blank" rel="noopener noreferrer" @endif>
                        {{ $navItem->localized('title', $locale) }}
                    </a>
                @endforeach
            </nav>
        @endif

        <small>&copy; {{ date('Y') }} {{ $siteName }}</small>
    </div>
</footer>
