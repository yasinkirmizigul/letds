@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        @if($siteSettings->localized('hero_notice'))
            <div class="mb-6 rounded-[28px] border border-border bg-white/80 px-6 py-5 text-sm text-muted-foreground shadow-sm">
                {{ $siteSettings->localized('hero_notice') }}
            </div>
        @endif

        @if($sliders->isNotEmpty())
            <section class="relative overflow-hidden rounded-3xl border border-border bg-slate-950 text-white shadow-[0_24px_80px_rgba(15,23,42,0.20)]" data-home-slider="true">
                @foreach($sliders as $slider)
                    <div class="{{ $loop->first ? 'relative' : 'absolute inset-0' }} min-h-[560px] lg:min-h-[600px] {{ $loop->first ? '' : 'opacity-0 pointer-events-none' }}" data-home-slide="true" aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                        @if($slider->imageUrl())
                            <img src="{{ $slider->imageUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover" style="{{ $slider->frameStyle() }}">
                        @endif
                        <div class="absolute inset-0" style="background-color: rgba(15, 23, 42, {{ min(90, max(10, (int) $slider->overlay_strength)) / 100 }});"></div>
                        <div class="relative z-10 flex min-h-[560px] flex-col justify-end gap-10 px-6 py-10 lg:min-h-[600px] lg:px-12 lg:py-14">
                            @if($slider->localized('badge'))
                                <div class="mb-4 inline-flex w-fit rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs uppercase tracking-[0.28em] text-white/80">
                                    {{ $slider->localized('badge') }}
                                </div>
                            @endif
                            <h1 class="max-w-3xl font-display text-4xl font-semibold leading-[1.1] md:text-6xl">{{ $slider->localized('title') }}</h1>
                            @if($slider->localized('subtitle'))
                                <p class="mt-5 max-w-2xl text-base leading-8 text-white/80 md:text-lg">{{ $slider->localized('subtitle') }}</p>
                            @endif
                            @if($slider->localized('body'))
                                <div class="mt-5 max-w-2xl text-sm leading-7 text-white/70">{{ $slider->localized('body') }}</div>
                            @endif
                            @if($slider->localized('cta_label') && $slider->localized('cta_url'))
                                <div class="mt-8 flex flex-wrap items-center gap-6">
                                    <a href="{{ $slider->localized('cta_url') }}" class="kt-btn kt-btn-primary">{{ $slider->localized('cta_label') }}</a>
                                    <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="text-sm font-medium text-white/80 hover:text-white">
                                        {{ $siteSettings->uiLine('nav_contact_label') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="absolute bottom-6 right-6 z-20 flex items-center gap-2">
                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-white/20 bg-white/10" data-home-slider-prev aria-label="Önceki slayt">
                        <i class="ki-outline ki-left text-white"></i>
                    </button>
                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-white/20 bg-white/10" data-home-slider-next aria-label="Sonraki slayt">
                        <i class="ki-outline ki-right text-white"></i>
                    </button>
                </div>

                <div class="absolute bottom-6 left-6 z-20 flex items-center gap-2">
                    @foreach($sliders as $slider)
                        <button type="button" class="h-2.5 w-8 rounded-full {{ $loop->first ? 'bg-white' : 'bg-white/35' }}" data-home-slide-indicator aria-label="Slayt {{ $loop->iteration }}"></button>
                    @endforeach
                </div>
            </section>
        @endif

        @if($globalCounters->isNotEmpty())
            <section class="mt-16" data-reveal>
                <div class="grid gap-y-10 border-y border-border py-10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($globalCounters as $counter)
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

        @if($featuredPages->isNotEmpty())
            <section class="mt-14" data-reveal>
                <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">{{ $siteSettings->uiLine('home_featured_kicker') }}</div>
                        <h2 class="mt-3 font-display text-3xl font-semibold md:text-4xl">{{ $siteSettings->uiLine('home_featured_heading') }}</h2>
                    </div>
                </div>

                <div>
                    @foreach($featuredPages as $page)
                        <a href="{{ $page->publicUrl($siteCurrentLocale) }}" data-reveal style="--reveal-delay: {{ ($loop->index % 6) * 80 }}ms"
                           class="group grid gap-4 border-t border-border py-8 transition-colors hover:bg-muted/40 sm:grid-cols-[64px_minmax(0,1fr)_auto] sm:items-baseline sm:gap-8 sm:px-4 last:border-b">
                            <div class="font-display text-sm text-muted-foreground">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                            <div>
                                <h3 class="font-display text-2xl font-semibold text-foreground transition-colors group-hover:text-primary">{{ $page->localized('title') }}</h3>
                                <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">{{ $page->excerptPreview(150) }}</p>
                            </div>
                            <div class="text-sm font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100">{{ $siteSettings->uiLine('home_featured_cta_label') }} →</div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($globalFaqs->isNotEmpty())
            <section class="mt-14 grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]" data-reveal>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">{{ $siteSettings->uiLine('home_faq_kicker') }}</div>
                    <h2 class="mt-3 font-display text-3xl font-semibold text-foreground">{{ $siteSettings->uiLine('home_faq_heading') }}</h2>
                    <p class="mt-4 text-sm leading-7 text-muted-foreground">
                        {{ $siteSettings->uiLine('home_faq_description') }}
                    </p>
                </div>

                <div class="divide-y divide-border border-y border-border">
                    @foreach($globalFaqs as $faq)
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

        <section class="mt-14 grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]" data-reveal>
            <div class="rounded-3xl bg-foreground p-6 text-background shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-background/60">{{ $siteSettings->uiLine('home_contact_kicker') }}</div>
                <h2 class="mt-3 font-display text-3xl font-semibold">{{ $siteSettings->uiLine('home_contact_heading') }}</h2>
                <div class="mt-4 grid gap-3 text-sm leading-7 text-background/70">
                    @if($siteSettings->contact_email)
                        <div>E-posta: {{ $siteSettings->contact_email }}</div>
                    @endif
                    @if($siteSettings->contact_phone)
                        <div>Telefon: {{ $siteSettings->contact_phone }}</div>
                    @endif
                    @if($siteSettings->whatsapp_phone)
                        <div>WhatsApp: {{ $siteSettings->whatsapp_phone }}</div>
                    @endif
                    @if($siteSettings->localized('address_line'))
                        <div>Adres: {{ $siteSettings->localized('address_line') }}</div>
                    @endif
                    @if($siteSettings->localized('office_hours'))
                        <div>Mesai: {{ $siteSettings->localized('office_hours') }}</div>
                    @endif
                </div>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary">{{ $siteSettings->uiLine('home_contact_primary_cta_label') }}</a>
                    <a href="{{ auth('member')->check() ? route('member.appointments.index', ['site_locale' => $siteCurrentLocale]) : route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">
                        {{ auth('member')->check() ? $siteSettings->uiLine('nav_member_panel_label') : $siteSettings->uiLine('nav_member_login_label') }}
                    </a>
                </div>
            </div>

            @if($siteSettings->map_embed_url)
                <div class="overflow-hidden rounded-3xl border border-border bg-white shadow-sm">
                    <iframe
                        src="{{ $siteSettings->map_embed_url }}"
                        title="{{ $siteSettings->localized('map_title') ?: 'Harita' }}"
                        class="h-full min-h-[360px] w-full"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>
            @endif
        </section>
    </div>

@endsection

@push('site_js')
    @vite('resources/js/site/cms.js')
@endpush
