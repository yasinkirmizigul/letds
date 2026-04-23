@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        @if($siteSettings->localized('hero_notice'))
            <div class="mb-6 rounded-[28px] border border-border bg-white/80 px-6 py-5 text-sm text-muted-foreground shadow-sm">
                {{ $siteSettings->localized('hero_notice') }}
            </div>
        @endif

        @if($sliders->isNotEmpty())
            <section class="relative overflow-hidden rounded-[36px] border border-border bg-slate-950 text-white shadow-[0_24px_80px_rgba(15,23,42,0.20)]" data-home-slider="true">
                @foreach($sliders as $slider)
                    <div class="{{ $loop->first ? '' : 'hidden' }} relative min-h-[520px]" data-home-slide="true">
                        @if($slider->imageUrl())
                            <img src="{{ $slider->imageUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover" style="{{ $slider->frameStyle() }}">
                        @endif
                        <div class="absolute inset-0" style="background-color: rgba(15, 23, 42, {{ min(90, max(10, (int) $slider->overlay_strength)) / 100 }});"></div>
                        <div class="relative z-10 grid min-h-[520px] gap-10 px-6 py-10 lg:grid-cols-[minmax(0,1fr)_280px] lg:px-12 lg:py-14">
                            <div class="flex flex-col justify-end">
                                @if($slider->localized('badge'))
                                    <div class="mb-4 inline-flex w-fit rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs uppercase tracking-[0.28em] text-white/80">
                                        {{ $slider->localized('badge') }}
                                    </div>
                                @endif
                                <h1 class="max-w-3xl text-4xl font-semibold leading-tight md:text-5xl">{{ $slider->localized('title') }}</h1>
                                @if($slider->localized('subtitle'))
                                    <p class="mt-5 max-w-2xl text-base leading-8 text-white/80 md:text-lg">{{ $slider->localized('subtitle') }}</p>
                                @endif
                                @if($slider->localized('body'))
                                    <div class="mt-5 max-w-2xl text-sm leading-7 text-white/70">{{ $slider->localized('body') }}</div>
                                @endif
                                @if($slider->localized('cta_label') && $slider->localized('cta_url'))
                                    <div class="mt-8">
                                        <a href="{{ $slider->localized('cta_url') }}" class="kt-btn kt-btn-primary">{{ $slider->localized('cta_label') }}</a>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-end justify-end">
                                <div class="rounded-[28px] border border-white/10 bg-white/10 p-5 backdrop-blur">
                                    <div class="text-xs uppercase tracking-[0.24em] text-white/60">Tema</div>
                                    <div class="mt-2 text-lg font-semibold">{{ \App\Models\Site\HomeSlider::themeOptions()[$slider->theme] ?? $slider->theme }}</div>
                                    <div class="mt-4 text-sm text-white/70">Görsel odak: {{ number_format($slider->crop_x, 0) }} / {{ number_format($slider->crop_y, 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="absolute bottom-6 right-6 z-20 flex items-center gap-2">
                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-white/20 bg-white/10" data-home-slider-prev>
                        <i class="ki-outline ki-left text-white"></i>
                    </button>
                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-white/20 bg-white/10" data-home-slider-next>
                        <i class="ki-outline ki-right text-white"></i>
                    </button>
                </div>

                <div class="absolute bottom-6 left-6 z-20 flex items-center gap-2">
                    @foreach($sliders as $slider)
                        <button type="button" class="h-2.5 w-8 rounded-full {{ $loop->first ? 'bg-white' : 'bg-white/35' }}" data-home-slide-indicator></button>
                    @endforeach
                </div>
            </section>
        @endif

        @if($globalCounters->isNotEmpty())
            <section class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach($globalCounters as $counter)
                    <div class="rounded-[28px] border border-border bg-white/85 p-6 shadow-sm">
                        <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <i class="{{ $counter->icon_class ?: 'ki-filled ki-chart-simple' }}"></i>
                        </div>
                        <div class="mt-5 text-4xl font-semibold text-foreground">
                            {{ $counter->localized('prefix') }}
                            <span data-countup-value="{{ $counter->value }}">0</span>
                            {{ $counter->localized('suffix') }}
                        </div>
                        <div class="mt-3 text-base font-medium text-foreground">{{ $counter->localized('label') }}</div>
                        @if($counter->localized('description'))
                            <div class="mt-2 text-sm leading-6 text-muted-foreground">{{ $counter->localized('description') }}</div>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if($featuredPages->isNotEmpty())
            <section class="mt-14">
                <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.28em] text-primary">{{ $siteSettings->uiLine('home_featured_kicker') }}</div>
                        <h2 class="mt-2 text-3xl font-semibold text-foreground">{{ $siteSettings->uiLine('home_featured_heading') }}</h2>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach($featuredPages as $page)
                        <a href="{{ $page->publicUrl($siteCurrentLocale) }}" class="group rounded-[32px] border border-border bg-white/85 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                            <div class="flex items-start justify-between gap-4">
                                <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                    <i class="{{ $page->icon_class ?: 'ki-filled ki-abstract-26' }}"></i>
                                </div>
                                <span class="rounded-full bg-muted/70 px-3 py-1 text-xs text-muted-foreground">/{{ $page->slugForLocale($siteCurrentLocale) }}</span>
                            </div>
                            <h3 class="mt-5 text-xl font-semibold text-foreground">{{ $page->localized('title') }}</h3>
                            <p class="mt-3 text-sm leading-7 text-muted-foreground">{{ $page->excerptPreview(150) }}</p>
                            <div class="mt-5 text-sm font-medium text-primary">{{ $siteSettings->uiLine('home_featured_cta_label') }}</div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($globalFaqs->isNotEmpty())
            <section class="mt-14 grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-primary">{{ $siteSettings->uiLine('home_faq_kicker') }}</div>
                    <h2 class="mt-2 text-3xl font-semibold text-foreground">{{ $siteSettings->uiLine('home_faq_heading') }}</h2>
                    <p class="mt-4 text-sm leading-7 text-muted-foreground">
                        {{ $siteSettings->uiLine('home_faq_description') }}
                    </p>
                </div>

                <div class="grid gap-4">
                    @foreach($globalFaqs as $faq)
                        <details class="rounded-[28px] border border-border bg-white/85 p-5 shadow-sm">
                            <summary class="cursor-pointer list-none text-base font-semibold text-foreground">
                                {{ $faq->localized('question') }}
                            </summary>
                            <div class="mt-4 text-sm leading-7 text-muted-foreground">{!! nl2br(e($faq->localized('answer'))) !!}</div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-14 grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="rounded-[32px] border border-border bg-white/85 p-6 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.28em] text-primary">{{ $siteSettings->uiLine('home_contact_kicker') }}</div>
                <h2 class="mt-2 text-3xl font-semibold text-foreground">{{ $siteSettings->uiLine('home_contact_heading') }}</h2>
                <div class="mt-4 grid gap-3 text-sm leading-7 text-muted-foreground">
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
                <div class="overflow-hidden rounded-[32px] border border-border bg-white shadow-sm">
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

    @vite('resources/js/site/cms.js')
@endsection
