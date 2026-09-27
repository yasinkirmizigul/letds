@extends('site.layouts.main.app')

@php
    $teamCopy = $siteCurrentLocale === 'en'
        ? [
            'eyebrow' => 'PROBABLUE / OUR TEAM',
            'heading' => 'The experts behind the analysis.',
            'intro' => 'Method, interpretation and communication come together in every project. Meet our experts.',
            'region' => 'Our experts',
            'default_role' => 'Statistics Expert',
            'photo' => 'profile photo',
            'previous' => 'Previous expert',
            'next' => 'Next expert',
            'empty' => 'Expert profiles will appear here when they are added.',
        ]
        : [
            'eyebrow' => 'PROBABLUE / EKİBİMİZ',
            'heading' => 'Analizin arkasındaki uzmanlar.',
            'intro' => 'Her çalışmada yöntem, yorum ve iletişim bir arada. Uzmanlarımızla tanışın.',
            'region' => 'Uzmanlarımız',
            'default_role' => 'İstatistik Uzmanı',
            'photo' => 'profil fotoğrafı',
            'previous' => 'Önceki uzman',
            'next' => 'Sonraki uzman',
            'empty' => 'Uzman profilleri eklendiğinde burada görünecek.',
        ];
@endphp

@section('content')
    <div class="mx-auto max-w-[96rem] px-3 py-8 sm:px-4 lg:px-6">
        <div class="max-w-3xl">
            @if($page->localized('hero_kicker'))
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                    {{ $page->localized('hero_kicker') }}
                </div>
            @endif

            <h1 class="mt-3 font-display text-3xl font-semibold text-foreground md:text-4xl">{{ $page->localized('title') }}</h1>

            @if($page->localized('excerpt'))
                <p class="mt-4 max-w-2xl text-base leading-8 text-muted-foreground">{{ $page->localized('excerpt') }}</p>
            @endif
        </div>

        @if($page->featuredUrl())
            <div class="mt-8 aspect-[21/9] overflow-hidden rounded-3xl">
                <img src="{{ $page->featuredUrl() }}" alt="" class="h-full w-full object-cover">
            </div>
        @endif

        <section class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
            <article class="rounded-3xl border border-border bg-background p-6 leading-8 text-foreground lg:p-10">
                {!! \App\Support\Security\HtmlSanitizer::sanitize($page->localized('content')) !!}
            </article>

            <aside class="grid gap-5 self-start lg:sticky lg:top-24">
                <div class="rounded-2xl border border-border bg-background p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{{ $siteSettings->uiLine('page_summary_label') }}</div>
                    <div class="mt-4 grid gap-2 text-sm text-muted-foreground">
                        <div>{{ $siteSettings->uiLine('page_reading_time_label') }}: {{ $page->readingTimeMinutes() }} dk</div>
                        <div>{{ $siteSettings->uiLine('page_link_label') }}: /{{ $page->slugForLocale($siteCurrentLocale) }}</div>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-background p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{{ $siteSettings->uiLine('page_quick_actions_label') }}</div>
                    <div class="mt-4 flex flex-col gap-3">
                        <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary w-full">{{ $siteSettings->uiLine('page_send_message_label') }}</a>
                        <a href="{{ auth('member')->check() ? route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) : route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light w-full">
                            {{ auth('member')->check() ? $siteSettings->uiLine('nav_member_panel_label') : $siteSettings->uiLine('nav_member_login_label') }}
                        </a>
                    </div>
                </div>
            </aside>
        </section>

        @if($page->slug === 'hakkimizda')
            <section class="site-team" aria-labelledby="site-team-title" data-site-team>
                <div class="site-team__intro">
                    <span class="site-team__eyebrow">{{ $teamCopy['eyebrow'] }}</span>
                    <h2 id="site-team-title">{{ $teamCopy['heading'] }}</h2>
                    <p>{{ $teamCopy['intro'] }}</p>
                </div>

                @if($teamExperts->isNotEmpty())
                <div class="site-team__carousel" role="region" aria-roledescription="carousel" aria-label="{{ $teamCopy['region'] }}">
                    <div class="site-team__track" data-site-team-track tabindex="0">
                        @foreach($teamExperts as $expert)
                            <article class="site-team__card" data-site-team-card role="button" tabindex="0" aria-label="{{ $expert->name }}, {{ $expert->title ?: $teamCopy['default_role'] }}">
                                <div class="site-team__portrait">
                                    @if($expert->avatar_media_id || filled($expert->avatar))
                                        <img src="{{ $expert->avatarUrl() }}" alt="{{ $expert->name }} {{ $teamCopy['photo'] }}" loading="lazy" draggable="false" width="440" height="550">
                                    @else
                                        <span class="site-team__monogram" aria-hidden="true">{{ mb_strtoupper(mb_substr($expert->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="site-team__panels">
                        @foreach($teamExperts as $expert)
                                <div class="site-team__details {{ $loop->first ? 'is-active' : '' }}" data-site-team-panel>
                                    <span class="site-team__role">{{ $expert->title ?: $teamCopy['default_role'] }}</span>
                                    <h3>{{ $expert->name }}</h3>
                                    @if($expert->bio)
                                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($expert->bio), 145) }}</p>
                                    @endif
                                    @if($expert->skillTags())
                                        <div class="site-team__skills">
                                            @foreach(array_slice($expert->skillTags(), 0, 3) as $skill)
                                                <span>{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                        @endforeach
                    </div>
                    @if($teamExperts->count() > 1)
                        <div class="site-team__controls">
                            <button type="button" data-site-team-prev aria-label="{{ $teamCopy['previous'] }}"><span aria-hidden="true">←</span></button>
                            <span data-site-team-count aria-live="polite"></span>
                            <button type="button" data-site-team-next aria-label="{{ $teamCopy['next'] }}"><span aria-hidden="true">→</span></button>
                        </div>
                    @endif
                </div>
                @else
                    <div class="site-team__empty">{{ $teamCopy['empty'] }}</div>
                @endif
            </section>
        @endif

        @if($page->show_counters && $page->counters->isNotEmpty())
            <section class="mt-16" data-reveal>
                <div class="grid gap-y-10 border-y border-border py-10 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($page->counters as $counter)
                        <div class="px-2 sm:px-6 {{ $loop->first ? '' : 'sm:border-l sm:border-border' }}">
                            <div class="font-display text-5xl font-medium tracking-tight text-foreground">
                                {{ $counter->localized('prefix') }}<span data-countup-value="{{ $counter->value }}">0</span>{{ $counter->localized('suffix') }}
                            </div>
                            <div class="mt-3 text-sm font-medium text-foreground">{{ $counter->localized('label') }}</div>
                            @if($counter->localized('description'))
                                <div class="mt-1 text-sm leading-6 text-muted-foreground">{{ $counter->localized('description') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if($page->show_faqs && $page->faqs->isNotEmpty())
            <section class="mt-14" data-reveal>
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">SSS</div>
                    <h2 class="mt-3 font-display text-3xl font-semibold text-foreground">{{ $siteSettings->uiLine('page_faq_heading') }}</h2>
                </div>
                <div class="divide-y divide-border border-y border-border">
                    @foreach($page->faqs as $faq)
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-semibold text-foreground">
                                {{ $faq->localized('question') }}
                                <span class="text-xl font-light text-muted-foreground transition-transform duration-300 group-open:rotate-45">+</span>
                            </summary>
                            <div class="mt-3 max-w-2xl text-sm leading-7 text-muted-foreground">{!! nl2br(e($faq->localized('answer'))) !!}</div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

@endsection

@push('site_js')
    @vite('resources/js/site/cms.js')
@endpush
