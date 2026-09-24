@php
    $sectionType = $section['type'] ?? null;
    $sectionPageLink = match ($sectionType) {
        'services' => [
            'label' => 'Hizmetler',
            'url' => \App\Support\Site\SiteLocalization::localizedRoute('site.services.index', locale: $locale) . '#hizmetler',
        ],
        'process' => [
            'label' => 'Hizmet Süreci',
            'url' => \App\Support\Site\SiteLocalization::localizedRoute('site.services.index', locale: $locale) . '#nasil-ilerliyoruz',
        ],
        default => null,
    };
@endphp

<section
    id="{{ $sectionAnchor ?? 'home-feature-' . $section['id'] }}"
    class="home-feature-section"
    data-surface="{{ $section['surface'] }}"
    data-alignment="{{ $section['alignment'] }}"
    data-section-type="{{ $section['type'] }}"
    style="--home-feature-custom-accent: {{ $section['accent_color'] }}; --home-feature-columns: {{ $section['columns'] }};"
    aria-labelledby="home-feature-title-{{ $section['id'] }}"
>
    <div class="home-feature-section__glow" aria-hidden="true"></div>
    <div class="home-feature-container">
        <header
            class="home-feature-heading et-in-viewport-check"
            et-anim="feature-rise"
            et-anim-duration="620"
            et-anim-delay="0"
            et-anim-easing="cubic-bezier(.2,.8,.2,1)"
        >
            <div>
                @if($section['eyebrow'] !== '')
                    <span class="home-feature-eyebrow">{{ $section['eyebrow'] }}</span>
                @endif
                <h2 id="home-feature-title-{{ $section['id'] }}">{{ $section['title'] }}</h2>
            </div>
            @if($section['description'] !== '')
                <p>{{ $section['description'] }}</p>
            @endif
        </header>

        <div class="home-feature-grid">
            @foreach($section['items'] as $item)
                <article
                    class="home-feature-card et-in-viewport-check"
                    et-anim="feature-rise"
                    et-anim-duration="620"
                    et-anim-delay="{{ min(360, $loop->index * 90) }}"
                    et-anim-easing="cubic-bezier(.2,.8,.2,1)"
                >
                    <div class="home-feature-card__topline">
                        <span class="home-feature-card__icon">
                            @include('site.partials.homepage-feature-icon', ['icon' => $item['icon']])
                        </span>
                        <span class="home-feature-card__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h3>{{ $item['title'] }}</h3>
                    @php($descriptionLines = collect(preg_split('/\r\n|\r|\n/', $item['description']))->map(fn ($line) => trim($line))->filter()->values())
                    @if($descriptionLines->count() > 1)
                        <ul class="home-feature-card__list">
                            @foreach($descriptionLines as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p>{{ $item['description'] }}</p>
                    @endif

                    @if($item['link_url'] && $item['link_label'] !== '')
                        <a href="{{ $item['link_url'] }}" class="home-feature-card__link">
                            <span>{{ $item['link_label'] }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </article>
            @endforeach
        </div>

        @if(($section['type'] ?? null) === 'process')
            <aside class="home-process-note et-in-viewport-check" et-anim="feature-rise" et-anim-duration="620" et-anim-delay="180">
                <span>Süre &amp; Fiyatlandırma</span>
                <p>Çalışma süresi ve ücret; projenin kapsamı, uygulanacak testler, veri yapısı ve analiz yoğunluğuna göre belirlenir. Ön görüşmenin ardından tahmini teslim süresi ve fiyatlandırma şeffaf biçimde paylaşılır.</p>
            </aside>
        @endif

        @if($sectionPageLink)
            <div class="home-feature-section__route et-in-viewport-check" et-anim="feature-rise" et-anim-duration="620" et-anim-delay="220">
                @include('site.home-sections.partials.page-link', [
                    'url' => $sectionPageLink['url'],
                    'label' => $sectionPageLink['label'],
                    'variant' => 'feature',
                ])
            </div>
        @endif
    </div>
</section>
