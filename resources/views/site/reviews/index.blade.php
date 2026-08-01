@extends('site.layouts.main.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-6">
        <div class="flex flex-col gap-5 border-b border-border pb-8 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase text-primary">Hizmet Deneyimi</div>
                <h1 class="mt-4 font-display text-3xl font-semibold text-foreground md:text-4xl">Değerlendirmelerim</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-muted-foreground">
                    Tamamlanan randevu ve siparişlerinizi puanlayın; daha önce verdiğiniz yanıtları görüntüleyin.
                </p>
            </div>
            <a href="{{ route('member.account.show', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">Hesabıma Dön</a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="border border-border bg-background p-5">
                <div class="text-sm text-muted-foreground">Yanıt Bekleyen</div>
                <div class="mt-2 text-3xl font-semibold text-warning">{{ $stats['pending'] }}</div>
            </div>
            <div class="border border-border bg-background p-5">
                <div class="text-sm text-muted-foreground">Tamamlanan</div>
                <div class="mt-2 text-3xl font-semibold text-success">{{ $stats['completed'] }}</div>
            </div>
            <div class="border border-border bg-background p-5">
                <div class="text-sm text-muted-foreground">Puan Ortalamam</div>
                <div class="mt-2 flex items-baseline gap-2"><span class="text-3xl font-semibold text-warning">{{ number_format($stats['average'], 1, ',', '.') }}</span><span class="text-muted-foreground">/ 5</span></div>
            </div>
        </div>

        <section class="mt-10 grid gap-4">
            @forelse($reviews as $review)
                <article class="border border-border bg-background p-6">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="kt-badge kt-badge-sm kt-badge-light-primary">{{ $review->serviceTypeLabel() }}</span>
                                <span class="{{ $review->statusBadgeClass() }}">{{ $review->statusLabel() }}</span>
                            </div>
                            <h2 class="mt-4 text-lg font-semibold text-foreground">{{ $review->service_title }}</h2>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                <span>{{ $review->service_reference ?: 'Referans yok' }}</span>
                                <span>{{ $review->service_completed_at?->format('d.m.Y H:i') ?: '-' }}</span>
                                @if($review->provider)<span>{{ $review->provider->name }}</span>@endif
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-4">
                            @if($review->overall_rating)
                                <div class="text-right">
                                    <div class="text-xl text-warning" aria-label="{{ $review->overall_rating }} yıldız">
                                        @foreach(range(1, 5) as $star)<span class="{{ $star <= $review->overall_rating ? 'text-warning' : 'text-muted-foreground/30' }}">★</span>@endforeach
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ $review->submitted_at?->format('d.m.Y H:i') }}</div>
                                </div>
                            @endif
                            <a href="{{ route('member.reviews.show', ['serviceReview' => $review, 'site_locale' => $siteCurrentLocale]) }}" class="{{ $review->isPending() ? 'kt-btn kt-btn-primary' : 'kt-btn kt-btn-light' }}">
                                {{ $review->isPending() ? 'Şimdi Değerlendir' : 'Yanıtı Gör' }}
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="border border-dashed border-border px-6 py-14 text-center">
                    <div class="text-lg font-semibold text-foreground">Değerlendirilecek hizmet bulunmuyor.</div>
                    <div class="mt-2 text-sm text-muted-foreground">Tamamlanan randevu veya siparişleriniz burada görünecek.</div>
                </div>
            @endforelse
        </section>

        <div class="mt-6">{{ $reviews->links() }}</div>
    </div>
@endsection
