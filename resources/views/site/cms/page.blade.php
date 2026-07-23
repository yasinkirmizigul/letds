@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
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
