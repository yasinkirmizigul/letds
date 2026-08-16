@extends('site.layouts.main.app')

@php
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqs->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq->localized('question'),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq->localized('answer'),
            ],
        ])->values()->all(),
    ];
    $hasFilters = $search !== '' || $selectedGroup !== '';
@endphp

@push('site_meta')
    <script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <section class="relative overflow-hidden border-b border-border bg-muted/30">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -right-28 -top-36 size-80 rounded-full bg-primary/10 blur-3xl"></div>
            <div class="absolute -bottom-32 left-[12%] size-72 rounded-full bg-success/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl gap-6 px-4 py-9 sm:py-11 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-end lg:px-6 lg:py-14">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                    <i class="ki-outline ki-message-question text-base" aria-hidden="true"></i>
                    {{ $siteSettings->uiLine('faq_page_kicker') }}
                </div>
                <h1 class="mt-4 max-w-2xl font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl lg:text-5xl">
                    {{ $siteSettings->uiLine('faq_page_heading') }}
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg">
                    {{ $siteSettings->uiLine('faq_page_description') }}
                </p>
            </div>

            <div class="rounded-3xl border border-border bg-background/85 p-5 shadow-sm backdrop-blur sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm text-muted-foreground">Yayınlanan yanıt</div>
                        <div class="mt-1 font-display text-4xl font-semibold text-foreground">{{ $totalFaqCount }}</div>
                    </div>
                    <span class="grid size-14 place-items-center rounded-2xl bg-primary/10 text-primary">
                        <i class="ki-outline ki-book-open text-2xl" aria-hidden="true"></i>
                    </span>
                </div>
                <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div class="h-full rounded-full bg-primary" style="width: {{ $totalFaqCount > 0 ? 100 : 0 }}%"></div>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-6 lg:py-14">
        <form method="GET" action="{{ $faqIndexUrl }}" class="grid gap-3 rounded-3xl border border-border bg-background p-3 shadow-sm sm:grid-cols-[minmax(0,1fr)_240px_auto]" role="search">
            <label class="relative block">
                <span class="sr-only">SSS ara</span>
                <i class="ki-outline ki-magnifier pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-muted-foreground" aria-hidden="true"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    class="kt-input h-12 w-full pl-11"
                    placeholder="{{ $siteSettings->uiLine('faq_search_placeholder') }}"
                    autocomplete="off"
                >
            </label>

            <label>
                <span class="sr-only">Konu grubu</span>
                <select name="group" class="kt-select h-12 w-full">
                    <option value="">{{ $siteSettings->uiLine('faq_all_groups_label') }}</option>
                    @foreach($faqGroups as $group)
                        <option value="{{ $group }}" @selected($selectedGroup === $group)>{{ $group }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="kt-btn kt-btn-primary h-12 justify-center px-6">
                <i class="ki-outline ki-magnifier" aria-hidden="true"></i>
                Ara
            </button>
        </form>

        <div class="mt-5 flex flex-wrap items-center gap-2" aria-label="SSS konu filtreleri">
            <a href="{{ $allGroupsUrl }}" class="kt-btn kt-btn-sm {{ $selectedGroup === '' ? 'kt-btn-primary' : 'kt-btn-light' }}">
                {{ $siteSettings->uiLine('faq_all_groups_label') }}
            </a>
            @foreach($faqGroups as $group)
                <a href="{{ $groupUrls->get($group) }}" class="kt-btn kt-btn-sm {{ $selectedGroup === $group ? 'kt-btn-primary' : 'kt-btn-light' }}">
                    {{ $group }}
                </a>
            @endforeach
            @if($hasFilters)
                <a href="{{ $faqIndexUrl }}" class="kt-btn kt-btn-sm kt-btn-ghost ms-auto">
                    <i class="ki-outline ki-cross" aria-hidden="true"></i>
                    Filtreleri Temizle
                </a>
            @endif
        </div>

        @if($faqs->isNotEmpty())
            <div class="mt-10 grid gap-10 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-14">
                <aside class="hidden lg:block">
                    <div class="sticky top-28 border-l-2 border-border pl-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">Sonuç özeti</div>
                        <div class="mt-3 font-display text-3xl font-semibold text-foreground">{{ $faqs->count() }}</div>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            {{ $hasFilters ? 'Filtrelerle eşleşen yanıt' : 'İnceleyebileceğiniz yanıt' }}
                        </p>
                    </div>
                </aside>

                <div class="grid gap-12">
                    @foreach($groupedFaqs as $group => $items)
                        <section aria-labelledby="faq-group-{{ $loop->index }}">
                            <div class="mb-5 flex items-end justify-between gap-4 border-b border-border pb-4">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Konu {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                    <h2 id="faq-group-{{ $loop->index }}" class="mt-2 font-display text-2xl font-semibold text-foreground sm:text-3xl">{{ $group }}</h2>
                                </div>
                                <span class="rounded-full bg-muted px-3 py-1 text-xs font-semibold text-muted-foreground">{{ $items->count() }} yanıt</span>
                            </div>

                            <div class="grid gap-3">
                                @foreach($items as $faq)
                                    <details class="group rounded-2xl border border-border bg-background transition-colors open:border-primary/30 open:bg-primary/[0.03]" @if($search !== '' && $loop->first) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-5 text-base font-semibold text-foreground marker:hidden sm:px-6 sm:py-6 sm:text-lg">
                                            <span class="flex min-w-0 items-start gap-3">
                                                <span class="mt-0.5 hidden size-8 shrink-0 place-items-center rounded-xl bg-muted text-sm text-muted-foreground sm:grid">
                                                    {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                                </span>
                                                <span>{{ $faq->localized('question') }}</span>
                                            </span>
                                            <span class="grid size-8 shrink-0 place-items-center rounded-full border border-border bg-background text-lg font-light text-muted-foreground transition-transform duration-300 group-open:rotate-45 group-open:border-primary/30 group-open:text-primary" aria-hidden="true">+</span>
                                        </summary>
                                        <div class="px-5 pb-6 sm:pl-17 sm:pr-16">
                                            <div class="max-w-3xl border-l-2 border-primary/20 pl-4 text-sm leading-7 text-muted-foreground sm:text-base sm:leading-8">
                                                {!! nl2br(e($faq->localized('answer'))) !!}
                                            </div>
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-10 rounded-3xl border border-dashed border-border bg-muted/30 px-6 py-14 text-center sm:py-20">
                <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-background text-primary shadow-sm">
                    <i class="ki-outline ki-message-question text-3xl" aria-hidden="true"></i>
                </span>
                <h2 class="mt-6 font-display text-2xl font-semibold text-foreground">{{ $siteSettings->uiLine('faq_empty_heading') }}</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-muted-foreground">{{ $siteSettings->uiLine('faq_empty_description') }}</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    @if($hasFilters)
                        <a href="{{ $faqIndexUrl }}" class="kt-btn kt-btn-light">Filtreleri Temizle</a>
                    @endif
                    <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary">{{ $siteSettings->uiLine('faq_contact_cta_label') }}</a>
                </div>
            </div>
        @endif

        <section class="mt-14 overflow-hidden rounded-3xl border border-border bg-foreground px-6 py-8 text-background sm:px-8 lg:flex lg:items-center lg:justify-between lg:gap-8 lg:px-10 lg:py-10">
            <div class="max-w-2xl">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-background/60">Yanıtı bulamadınız mı?</div>
                <h2 class="mt-3 font-display text-2xl font-semibold sm:text-3xl">Sorunuzu doğrudan ekibimize iletin.</h2>
                <p class="mt-3 text-sm leading-7 text-background/70">İhtiyacınızı kısaca paylaşın; tercih ettiğiniz iletişim kanalından size dönüş yapalım.</p>
            </div>
            <a href="{{ route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn mt-6 shrink-0 border-background/20 bg-background text-foreground hover:bg-background/90 lg:mt-0">
                {{ $siteSettings->uiLine('faq_contact_cta_label') }}
                <i class="ki-outline ki-arrow-right" aria-hidden="true"></i>
            </a>
        </section>
    </div>
@endsection
