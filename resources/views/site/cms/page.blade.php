@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <section class="overflow-hidden rounded-[36px] border border-border bg-white/85 shadow-sm">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_420px]">
                <div class="p-6 lg:p-10">
                    @if($page->hero_kicker)
                        <div class="inline-flex rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-primary">
                            {{ $page->hero_kicker }}
                        </div>
                    @endif

                    <div class="mt-5 flex items-start gap-4">
                        <div class="inline-flex size-14 items-center justify-center rounded-3xl bg-primary/10 text-xl text-primary">
                            <i class="{{ $page->icon_class ?: 'ki-filled ki-abstract-26' }}"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-semibold text-foreground">{{ $page->title }}</h1>
                            @if($page->excerpt)
                                <p class="mt-4 max-w-3xl text-base leading-8 text-muted-foreground">{{ $page->excerpt }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="relative min-h-[280px] border-l border-border bg-slate-100">
                    @if($page->featuredUrl())
                        <img src="{{ $page->featuredUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                    @endif
                </div>
            </div>
        </section>

        <section class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
            <article class="rounded-[32px] border border-border bg-white/85 p-6 leading-8 text-foreground shadow-sm lg:p-10">
                {!! $page->content !!}
            </article>

            <aside class="grid gap-5 self-start lg:sticky lg:top-24">
                <div class="rounded-[28px] border border-border bg-white/85 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">Özet</div>
                    <div class="mt-4 grid gap-2 text-sm text-muted-foreground">
                        <div>Okuma süresi: {{ $page->readingTimeMinutes() }} dk</div>
                        <div>SEO skoru: %{{ $page->seoCompletenessScore() }}</div>
                        <div>Bağlantı: /{{ $page->slug }}</div>
                    </div>
                </div>

                <div class="rounded-[28px] border border-border bg-white/85 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">Hızlı Eylem</div>
                    <div class="mt-4 flex flex-col gap-3">
                        <a href="{{ route('site.contact-messages.create') }}" class="kt-btn kt-btn-primary w-full">Mesaj Gönder</a>
                        <a href="{{ auth('member')->check() ? route('member.appointments.index') : route('member.login') }}" class="kt-btn kt-btn-light w-full">
                            {{ auth('member')->check() ? 'Randevu Paneli' : 'Üye Girişi' }}
                        </a>
                    </div>
                </div>
            </aside>
        </section>

        @if($page->show_counters && $page->counters->isNotEmpty())
            <section class="mt-12 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach($page->counters as $counter)
                    <div class="rounded-[28px] border border-border bg-white/85 p-6 shadow-sm">
                        <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <i class="{{ $counter->icon_class ?: 'ki-filled ki-chart-simple' }}"></i>
                        </div>
                        <div class="mt-5 text-4xl font-semibold text-foreground">
                            {{ $counter->prefix }}
                            <span data-countup-value="{{ $counter->value }}">0</span>
                            {{ $counter->suffix }}
                        </div>
                        <div class="mt-3 text-base font-medium text-foreground">{{ $counter->label }}</div>
                        @if($counter->description)
                            <div class="mt-2 text-sm leading-6 text-muted-foreground">{{ $counter->description }}</div>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if($page->show_faqs && $page->faqs->isNotEmpty())
            <section class="mt-12">
                <div class="mb-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-primary">Sıkça Sorulan Sorular</div>
                    <h2 class="mt-2 text-3xl font-semibold text-foreground">Sayfa ile ilgili hızlı cevaplar</h2>
                </div>
                <div class="grid gap-4">
                    @foreach($page->faqs as $faq)
                        <details class="rounded-[28px] border border-border bg-white/85 p-5 shadow-sm">
                            <summary class="cursor-pointer list-none text-base font-semibold text-foreground">
                                {{ $faq->question }}
                            </summary>
                            <div class="mt-4 text-sm leading-7 text-muted-foreground">{!! nl2br(e($faq->answer)) !!}</div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    @vite('resources/js/site/cms.js')
@endsection
